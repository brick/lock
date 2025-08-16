<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util;

use Closure;

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

    public function synchronize(Closure $task): void
    {
        $this->client->sendCommand(new Command\Synchronize($this->lockIndex, $task));
    }

    public function trySynchronize(Closure $task): void
    {
        $this->client->sendCommand(new Command\TrySynchronize($this->lockIndex, $task));
    }

    public function trySynchronizeWithTimeout(int $timeoutSeconds, Closure $task): void
    {
        $this->client->sendCommand(new Command\TrySynchronizeWithTimeout($this->lockIndex, $timeoutSeconds, $task));
    }
}
