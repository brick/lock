<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Assert;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/**
 * Sends commands to the worker operating in a separate process.
 */
final class RemoteLock
{
    private readonly Process $process;
    private readonly InputStream $input;
    private bool $isKilled = false;

    public function __construct()
    {
        $this->process = new Process(['php', 'worker.php'], __DIR__ . '/..');
        $this->input = new InputStream();

        $this->process->setInput($this->input);
        $this->process->start();

        $this->sendCommand(new Command\Ping());
        $this->expectWithin('1s', 'PONG');
    }

    public function acquire(string $lockName): void
    {
        $this->sendCommand(new Command\Acquire([$lockName]));
    }

    public function tryAcquire(string $lockName): void
    {
        $this->sendCommand(new Command\TryAcquire([$lockName]));
    }

    public function tryAcquireWithTimeout(string $lockName, int $timeoutSeconds): void
    {
        $this->sendCommand(new Command\TryAcquireWithTimeout([$lockName], $timeoutSeconds));
    }

    public function release(?string $lockName = null): void
    {
        $this->sendCommand(new Command\Release($lockName));
    }

    public function wait(string $lockName): void
    {
        $this->sendCommand(new Command\Wait([$lockName]));
    }

    public function tryWaitWithTimeout(string $lockName, int $timeoutSeconds): void
    {
        $this->sendCommand(new Command\TryWaitWithTimeout([$lockName], $timeoutSeconds));
    }

    public function synchronizeReturn(
        string $lockName,
        int $taskDurationSeconds,
        string $taskReturnValue,
    ): void {
        $this->sendCommand(new Command\SynchronizeReturn(
            [$lockName],
            $taskDurationSeconds,
            $taskReturnValue,
        ));
    }

    public function synchronizeThrow(
        string $lockName,
        int $taskDurationSeconds,
        string $taskExceptionMessage,
    ): void {
        $this->sendCommand(new Command\SynchronizeThrow(
            [$lockName],
            $taskDurationSeconds,
            $taskExceptionMessage,
        ));
    }

    public function trySynchronizeReturn(
        string $lockName,
        int $taskDurationSeconds,
        string $taskReturnValue,
    ): void {
        $this->sendCommand(new Command\TrySynchronizeReturn(
            [$lockName],
            $taskDurationSeconds,
            $taskReturnValue,
        ));
    }

    public function trySynchronizeThrow(
        string $lockName,
        int $taskDurationSeconds,
        string $taskExceptionMessage,
    ): void {
        $this->sendCommand(new Command\TrySynchronizeThrow(
            [$lockName],
            $taskDurationSeconds,
            $taskExceptionMessage,
        ));
    }

    public function trySynchronizeWithTimeoutReturn(
        string $lockName,
        int $timeoutSeconds,
        int $taskDurationSeconds,
        string $taskReturnValue,
    ): void {
        $this->sendCommand(new Command\TrySynchronizeWithTimeoutReturn(
            [$lockName],
            $timeoutSeconds,
            $taskDurationSeconds,
            $taskReturnValue,
        ));
    }

    public function trySynchronizeWithTimeoutThrow(
        string $lockName,
        int $timeoutSeconds,
        int $taskDurationSeconds,
        string $taskExceptionMessage,
    ): void {
        $this->sendCommand(new Command\TrySynchronizeWithTimeoutThrow(
            [$lockName],
            $timeoutSeconds,
            $taskDurationSeconds,
            $taskExceptionMessage,
        ));
    }

    public function beginTransaction(): void
    {
        $this->sendCommand(new Command\BeginTransaction());
    }

    public function commit(): void
    {
        $this->sendCommand(new Command\Commit());
    }

    public function rollBack(): void
    {
        $this->sendCommand(new Command\RollBack());
    }

    /**
     * Expects that the worker produces the given output within the given duration.
     *
     * This method should pause for at most the given duration.
     */
    public function expectWithin(string $duration, string $expected): void
    {
        $durationSeconds = $this->parseDuration($duration);
        $startTime = microtime(true);

        while (true) {
            if ($this->process->isTerminated()) {
                Assert::fail(sprintf(
                    'Worker process has terminated unexpectedly with output: "%s" and error: "%s"',
                    trim($this->process->getOutput()),
                    trim($this->process->getErrorOutput()),
                ));
            }

            $output = $this->process->getIncrementalOutput();

            if ($output !== '') {
                $output = explode("\n", rtrim($output, "\n"));

                Assert::assertCount(1, $output, 'Expected a single output from worker, but received: ' . implode(', ', $output));
                Assert::assertSame($expected, $output[0]);

                return;
            }

            if (microtime(true) - $startTime > $durationSeconds) {
                break;
            }

            usleep(100_000);
        }

        Assert::fail('Worker did not respond within ' . $duration);
    }

    /**
     * Expects that the worker does not produce any output for the given duration.
     *
     * This method pauses for at least the given duration.
     */
    public function expectNothingAfter(string $duration): void
    {
        $durationSeconds = $this->parseDuration($duration);
        $startTime = microtime(true);

        while (true) {
            if ($this->process->isTerminated()) {
                Assert::fail(sprintf(
                    'Worker process has terminated unexpectedly with output: "%s" and error: "%s"',
                    trim($this->process->getOutput()),
                    trim($this->process->getErrorOutput()),
                ));
            }

            $output = $this->process->getIncrementalOutput();

            if ($output !== '') {
                $output = explode("\n", rtrim($output, "\n"));

                Assert::fail('Expected nothing, but received output from worker: ' . implode(', ', $output));
            }

            if (microtime(true) - $startTime > $durationSeconds) {
                break;
            }

            usleep(100_000);
        }
    }

    public function kill(): void
    {
        $this->process->stop(0);
        $this->isKilled = true;
    }

    private function sendCommand(CommandInterface $command): void
    {
        if ($this->isKilled) {
            throw new LogicException('Cannot send command after killing the remote lock.');
        }

        $command = json_encode([
            'className' => $command::class,
            'data' => $command,
        ], flags: JSON_THROW_ON_ERROR);

        $this->input->write("$command\n");
    }

    /**
     * Parsed a duration such as '1s' or '500ms' and returns the value in seconds.
     */
    private function parseDuration(string $duration): float
    {
        if (preg_match('/^([0-9]+)(s|ms)$/', $duration, $matches) !== 1) {
            throw new InvalidArgumentException('Invalid duration format: ' . $duration);
        }

        return (float) $matches[1] * match ($matches[2]) {
            's' => 1.0,
            'ms' => 0.001,
        };
    }

    public function __destruct()
    {
        $this->process->stop(0);
    }
}
