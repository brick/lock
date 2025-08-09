<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util;

use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/**
 * Sends commands to the worker operating in a separate process.
 */
final readonly class RemoteLock
{
    private Process $process;
    private InputStream $input;

    public function __construct()
    {
        $this->process = new Process(['php', 'worker.php'], __DIR__ . '/..');
        $this->input = new InputStream();

        $this->process->setInput($this->input);
        $this->process->start();

        $this->sendCommand('ping');
        $this->expectWithin('1s', 'PONG');
    }

    public function acquire(string $lockName): void
    {
        $this->sendCommand('acquire', [$lockName]);
    }

    public function tryAcquire(string $lockName): void
    {
        $this->sendCommand('tryAcquire', [$lockName]);
    }

    public function tryAcquireWithTimeout(string $lockName, int $timeoutSeconds): void
    {
        $this->sendCommand('tryAcquireWithTimeout', [$lockName], timeoutSeconds: $timeoutSeconds);
    }

    public function release(string $lockName): void
    {
        $this->sendCommand('release', [$lockName]);
    }

    public function wait(string $lockName): void
    {
        $this->sendCommand('wait', [$lockName]);
    }

    public function tryWaitWithTimeout(string $lockName, int $timeoutSeconds): void
    {
        $this->sendCommand('tryWaitWithTimeout', [$lockName], timeoutSeconds: $timeoutSeconds);
    }

    public function synchronizeReturningTask(
        string $lockName,
        int $taskDurationSeconds,
        string $taskReturnValue,
    ): void {
        $this->sendCommand(
            'synchronize_return',
            [$lockName],
            taskDurationSeconds: $taskDurationSeconds,
            taskMessage: $taskReturnValue,
        );
    }

    public function synchronizeThrowingTask(
        string $lockName,
        int $taskDurationSeconds,
        string $taskExceptionMessage,
    ): void {
        $this->sendCommand(
            'synchronize_exception',
            [$lockName],
            taskDurationSeconds: $taskDurationSeconds,
            taskMessage: $taskExceptionMessage,
        );
    }

    public function trySynchronizeReturningTask(
        string $lockName,
        int $taskDurationSeconds,
        string $taskReturnValue,
    ): void {
        $this->sendCommand(
            'trySynchronize_return',
            [$lockName],
            taskDurationSeconds: $taskDurationSeconds,
            taskMessage: $taskReturnValue,
        );
    }

    public function trySynchronizeThrowingTask(
        string $lockName,
        int $taskDurationSeconds,
        string $taskExceptionMessage,
    ): void {
        $this->sendCommand(
            'trySynchronize_exception',
            [$lockName],
            taskDurationSeconds: $taskDurationSeconds,
            taskMessage: $taskExceptionMessage,
        );
    }

    public function trySynchronizeWithTimeoutReturningTask(
        string $lockName,
        int $timeoutSeconds,
        int $taskDurationSeconds,
        string $taskReturnValue,
    ): void {
        $this->sendCommand(
            'trySynchronizeWithTimeout_return',
            [$lockName],
            timeoutSeconds: $timeoutSeconds,
            taskDurationSeconds: $taskDurationSeconds,
            taskMessage: $taskReturnValue,
        );
    }

    public function trySynchronizeWithTimeoutThrowingTask(
        string $lockName,
        int $timeoutSeconds,
        int $taskDurationSeconds,
        string $taskExceptionMessage,
    ): void {
        $this->sendCommand(
            'trySynchronizeWithTimeout_exception',
            [$lockName],
            timeoutSeconds: $timeoutSeconds,
            taskDurationSeconds: $taskDurationSeconds,
            taskMessage: $taskExceptionMessage,
        );
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

    /**
     * @param string[] $lockNames
     */
    private function sendCommand(
        string $operation,
        array $lockNames = [],
        int $timeoutSeconds = 0,
        int $taskDurationSeconds = 0,
        string $taskMessage = '',
    ): void {
        $command = json_encode([
            'operation' => $operation,
            'lockNames' => $lockNames,
            'timeoutSeconds' => $timeoutSeconds,
            'taskDurationSeconds' => $taskDurationSeconds,
            'taskMessage' => $taskMessage,
        ]);

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
