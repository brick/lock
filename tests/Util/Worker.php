<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util;

use Brick\Lock\LockFactory;

/**
 * Executed in a separate process; receives lock commands from the test suite via STDIN and executes them.
 */
class Worker
{
    public function run(): never
    {
        $result = LockDriverFactory::getDriver();

        if ($result instanceof LockDriverFactoryFailure) {
            foreach ($result->errorMessages as $message) {
                $this->write($message);
            }
            exit(1);
        }

        $driver = $result->lockDriver;
        $lockFactory = new LockFactory($driver);

        while (($message = fgets(STDIN)) !== false) {
            /** @var object{
             *     operation: string,
             *     lockNames: list<string>,
             *     timeoutSeconds: int,
             *     taskDurationSeconds: int
             * } $command
             */
            $command = json_decode($message, flags: JSON_THROW_ON_ERROR);

            if (count($command->lockNames) === 1) {
                $lock = $lockFactory->createLock($command->lockNames[0]);
            } else {
                $lock = $lockFactory->createMultiLock($command->lockNames);
            }

            $successTask = function () use ($command) {
                sleep($command->taskDurationSeconds);

                return 'TASK_OUTPUT';
            };

            $failureTask = function () use ($command) {
                sleep($command->taskDurationSeconds);

                throw new Exception('TASK_EXCEPTION_MESSAGE');
            };

            switch ($command->operation) {
                case 'ping':
                    $this->write('PONG');
                    break;

                case 'acquire':
                    $lock->acquire();
                    $this->writeAcquired(true);
                    break;

                case 'tryAcquire':
                    $this->writeAcquired($lock->tryAcquire());
                    break;

                case 'tryAcquireWithTimeout':
                    $this->writeAcquired($lock->tryAcquireWithTimeout($command->timeoutSeconds));
                    break;

                case 'release':
                    $lock->release();
                    $this->write('RELEASED');
                    break;

                case 'wait':
                    $lock->wait();
                    $this->writeWaitSuccess(true);
                    break;

                case 'tryWaitWithTimeout':
                    $this->writeWaitSuccess($lock->tryWaitWithTimeout($command->timeoutSeconds));
                    break;

                case 'synchronizeSuccess':
                    $output = $lock->synchronize($successTask);
                    $this->writeSyncSuccess(true);
                    $this->write($output);
                    break;

                case 'trySynchronize':
                    $result = $lock->trySynchronize($successTask);
                    $this->writeSyncSuccess($result->isLockSuccess());
                    break;

                case 'trySynchronizeWithTimeout':
                    $result = $lock->trySynchronizeWithTimeout($command->timeoutSeconds, $successTask);
                    $this->writeSyncSuccess($result->isLockSuccess());
                    break;

                default:
                    $this->writeError('Unknown operation: ' . $command->operation);
                    exit(1);
            }
        }

        // The process is supposed to be killed by the parent process.
        $this->write('Unexpected end of input');
        exit(1);
    }

    private function write(string $message): void
    {
        fwrite(STDOUT, "$message\n");
        fflush(STDOUT);
    }

    private function writeAcquired(bool $acquired): void
    {
        $this->write($acquired ? 'ACQUIRED' : 'NOT_ACQUIRED');
    }

    private function writeWaitSuccess(bool $isSuccess): void
    {
        $this->write($isSuccess ? 'WAIT_SUCCESS' : 'WAIT_FAILURE');
    }

    private function writeSyncSuccess(bool $isSuccess): void
    {
        $this->write($isSuccess ? 'SYNC_SUCCESS' : 'SYNC_FAILURE');
    }
}
