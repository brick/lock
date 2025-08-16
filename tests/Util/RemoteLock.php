<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util;

/**
 * Represents a lock on the remote worker.
 * Contrary to the methods on LockInterface, these methods are not synchronous.
 */
final readonly class RemoteLock
{
    public function __construct(
        private RemoteWorker $client,
        private int $lockIndex,
    ) {
    }

    public function acquire(): void
    {
        $this->client->sendCommand(new Command\Acquire($this->lockIndex));
    }

    public function tryAcquire(): void
    {
        $this->client->sendCommand(new Command\TryAcquire($this->lockIndex));
    }

    public function tryAcquireWithTimeout(int $seconds): void
    {
        $this->client->sendCommand(new Command\TryAcquireWithTimeout($this->lockIndex, $seconds));
    }

    public function release(): void
    {
        $this->client->sendCommand(new Command\Release($this->lockIndex));
    }

    public function wait(): void
    {
        $this->client->sendCommand(new Command\Wait($this->lockIndex));
    }

    public function tryWaitWithTimeout(int $seconds): void
    {
        $this->client->sendCommand(new Command\TryWaitWithTimeout($this->lockIndex, $seconds));
    }

    public function synchronizeReturn(
        int $taskDurationSeconds,
        string $taskReturnValue,
    ): void {
        $this->client->sendCommand(new Command\SynchronizeReturn(
            $this->lockIndex,
            $taskDurationSeconds,
            $taskReturnValue,
        ));
    }

    public function synchronizeThrow(
        int $taskDurationSeconds,
        string $taskExceptionMessage,
    ): void {
        $this->client->sendCommand(new Command\SynchronizeThrow(
            $this->lockIndex,
            $taskDurationSeconds,
            $taskExceptionMessage,
        ));
    }

    public function trySynchronizeReturn(
        int $taskDurationSeconds,
        string $taskReturnValue,
    ): void {
        $this->client->sendCommand(new Command\TrySynchronizeReturn(
            $this->lockIndex,
            $taskDurationSeconds,
            $taskReturnValue,
        ));
    }

    public function trySynchronizeThrow(
        int $taskDurationSeconds,
        string $taskExceptionMessage,
    ): void {
        $this->client->sendCommand(new Command\TrySynchronizeThrow(
            $this->lockIndex,
            $taskDurationSeconds,
            $taskExceptionMessage,
        ));
    }

    public function trySynchronizeWithTimeoutReturn(
        int $timeoutSeconds,
        int $taskDurationSeconds,
        string $taskReturnValue,
    ): void {
        $this->client->sendCommand(new Command\TrySynchronizeWithTimeoutReturn(
            $this->lockIndex,
            $timeoutSeconds,
            $taskDurationSeconds,
            $taskReturnValue,
        ));
    }

    public function trySynchronizeWithTimeoutThrow(
        int $timeoutSeconds,
        int $taskDurationSeconds,
        string $taskExceptionMessage,
    ): void {
        $this->client->sendCommand(new Command\TrySynchronizeWithTimeoutThrow(
            $this->lockIndex,
            $timeoutSeconds,
            $taskDurationSeconds,
            $taskExceptionMessage,
        ));
    }
}
