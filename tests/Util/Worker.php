<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util;

use Brick\Lock\Exception\LockAcquireException;
use Brick\Lock\Exception\LockException;
use Brick\Lock\LockFactory;
use Brick\Lock\LockInterface;
use Closure;
use Exception;

/**
 * Executed in a separate process; receives lock commands from the test suite via STDIN and executes them.
 */
class Worker
{
    public function run(): never
    {
        try {
            $lockDriverWithInfo = LockDriverFactory::getDriver();
        } catch (LockDriverFactoryException $e) {
            foreach ($e->errorMessages as $errorMessage) {
                $this->writeErr($errorMessage);
            }
            exit(1);
        }

        $lockDriver = $lockDriverWithInfo->lockDriver;
        $lockFactory = new LockFactory($lockDriver);

        while (($line = fgets(STDIN)) !== false) {
            /** @var object{
             *     operation: string,
             *     lockNames: non-empty-list<string>,
             *     timeoutSeconds: int,
             *     taskDurationSeconds: int,
             *     taskMessage: string
             * } $command
             */
            $command = json_decode($line, flags: JSON_THROW_ON_ERROR);

            if (count($command->lockNames) === 1) {
                $lock = $lockFactory->createLock($command->lockNames[0]);
            } else {
                $lock = $lockFactory->createMultiLock($command->lockNames);
            }

            $returnTask = function () use ($command) {
                sleep($command->taskDurationSeconds);

                return $command->taskMessage;
            };

            $exceptionTask = function () use ($command) {
                sleep($command->taskDurationSeconds);

                throw new Exception($command->taskMessage);
            };

            switch ($command->operation) {
                case 'ping':
                    $this->write('PONG');
                    break;

                case 'acquire':
                    $this->guard(function() use ($lock) {
                        $lock->acquire();
                        $this->writeAcquireResult(true);
                    });
                    break;

                case 'tryAcquire':
                    $this->guard(function() use ($lock) {
                        $this->writeAcquireResult(
                            $lock->tryAcquire(),
                        );
                    });
                    break;

                case 'tryAcquireWithTimeout':
                    $this->guard(function() use ($lock, $command) {
                        $this->writeAcquireResult(
                            $lock->tryAcquireWithTimeout($command->timeoutSeconds),
                        );
                    });
                    break;

                case 'release':
                    $this->guard(function() use ($lock) {
                        $lock->release();
                        $this->write('RELEASED');
                    });
                    break;

                case 'wait':
                    $this->guard(function() use ($lock) {
                        $lock->wait();
                        $this->writeWaitResult(true);
                    });
                    break;

                case 'tryWaitWithTimeout':
                    $this->guard(function() use ($lock, $command) {
                        $this->writeWaitResult(
                            $lock->tryWaitWithTimeout($command->timeoutSeconds),
                        );
                    });
                    break;

                case 'synchronize_return':
                    $this->guard(function() use ($lock, $returnTask) {
                        $this->doSynchronize($lock, $returnTask);
                    });
                    break;

                case 'synchronize_exception':
                    $this->guard(function() use ($lock, $exceptionTask) {
                        $this->doSynchronize($lock, $exceptionTask);
                    });
                    break;

                case 'trySynchronize_return':
                    $this->guard(function() use ($lock, $returnTask) {
                        $this->doTrySynchronize($lock, $returnTask);
                    });
                    break;

                case 'trySynchronize_exception':
                    $this->guard(function() use ($lock, $exceptionTask) {
                        $this->doTrySynchronize($lock, $exceptionTask);
                    });
                    break;

                case 'trySynchronizeWithTimeout_return':
                    $this->guard(function() use ($lock, $returnTask, $command) {
                        $this->doTrySynchronizeWithTimeout($lock, $returnTask, $command->timeoutSeconds);
                    });
                    break;

                case 'trySynchronizeWithTimeout_exception':
                    $this->guard(function() use ($lock, $exceptionTask, $command) {
                        $this->doTrySynchronizeWithTimeout($lock, $exceptionTask, $command->timeoutSeconds);
                    });
                    break;

                default:
                    $this->writeErr('Unknown operation: ' . $command->operation);
                    exit(1);
            }
        }

        // The process is supposed to be killed by the parent process.
        $this->writeErr('Unexpected end of input');
        exit(1);
    }

    /**
     * Executes the given closure, catching LockException.
     */
    private function guard(Closure $closure): void
    {
        try {
            $closure();
        } catch (LockException $e) {
            $shortName = (new \ReflectionClass($e))->getShortName();
            $this->write($shortName);
        }
    }

    /**
     * @param Closure(): string $task
     *
     * @return void
     */
    private function doSynchronize(LockInterface $lock, Closure $task): void
    {
        $returnValue = null;
        $exception = null;

        try {
            $returnValue = $lock->synchronize($task);
        } catch (Exception $e) {
            $exception = $e;
        }

        $this->writeSyncResult(true, $returnValue, $exception?->getMessage());
    }

    /**
     * @param Closure(): string $task
     *
     * @return void
     */
    private function doTrySynchronize(LockInterface $lock, Closure $task): void
    {
        $returnValue = null;
        $exception = null;

        try {
            $synchronizeSuccess = $lock->trySynchronize($task);
            $returnValue = $synchronizeSuccess?->returnValue;
            $isLockSuccess = $synchronizeSuccess !== null;
        } catch (Exception $e) {
            $exception = $e;
            $isLockSuccess = ! $e instanceof LockAcquireException;
        }

        $this->writeSyncResult($isLockSuccess, $returnValue, $exception?->getMessage());
    }

    /**
     * @param Closure(): string $task
     *
     * @return void
     */
    private function doTrySynchronizeWithTimeout(LockInterface $lock, Closure $task, int $timeoutSeconds): void
    {
        $returnValue = null;
        $exception = null;

        try {
            $synchronizeSuccess = $lock->trySynchronizeWithTimeout($timeoutSeconds, $task);
            $returnValue = $synchronizeSuccess?->returnValue;
            $isLockSuccess = $synchronizeSuccess !== null;
        } catch (Exception $e) {
            $exception = $e;
            $isLockSuccess = ! $e instanceof LockAcquireException;
        }

        $this->writeSyncResult($isLockSuccess, $returnValue, $exception?->getMessage());
    }

    private function write(string $message): void
    {
        fwrite(STDOUT, "$message\n");
        fflush(STDOUT);
    }

    private function writeErr(string $message): void
    {
        fwrite(STDERR, "$message\n");
        fflush(STDERR);
    }

    private function writeAcquireResult(bool $isAcquired): void
    {
        $this->write($isAcquired ? 'ACQUIRED' : 'NOT_ACQUIRED');
    }

    private function writeWaitResult(bool $isSuccess): void
    {
        $this->write($isSuccess ? 'WAIT_SUCCESS' : 'WAIT_FAILURE');
    }

    private function writeSyncResult(bool $isLockSuccess, ?string $returnValue, ?string $exceptionMessage): void
    {
        $writeMessage = $isLockSuccess ? 'SYNC_LOCK_SUCCESS' : 'SYNC_LOCK_FAILURE';

        if ($returnValue !== null) {
            $writeMessage .= ';RETURN:' . $returnValue;
        }

        if ($exceptionMessage !== null) {
            $writeMessage .= ';EXCEPTION:' . $exceptionMessage;
        }

        $this->write($writeMessage);
    }
}
