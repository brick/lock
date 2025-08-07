<?php

declare(strict_types=1);

namespace Brick\Lock;

final readonly class Lock extends AbstractLock
{
    public function __construct(
        private LockDriverInterface $driver,
        private string              $lockName,
    ) {
    }

    public function acquire(): void
    {
        $this->driver->acquire($this->lockName);
    }

    public function tryAcquire(): bool
    {
        return $this->driver->tryAcquire($this->lockName);
    }

    public function tryAcquireWithTimeout(int $seconds): bool
    {
        if ($seconds <= 0) {
            throw new LockException('Timeout must be a positive integer.');
        }

        return $this->driver->tryAcquireWithTimeout($this->lockName, $seconds);
    }

    public function release(): void
    {
        $this->driver->release($this->lockName);
    }
}
