<?php

declare(strict_types=1);

namespace Brick\Lock;

use Closure;

abstract readonly class AbstractLock implements LockInterface
{
    public function wait(): void
    {
        $this->acquire();
        $this->release();
    }

    public function tryWaitWithTimeout(int $seconds): bool
    {
        if ($this->tryAcquireWithTimeout($seconds)) {
            $this->release();

            return true;
        }

        return false;
    }

    public function synchronize(Closure $task): mixed
    {
        $this->acquire();

        try {
            return $task();
        } finally {
            $this->release();
        }
    }

    public function trySynchronize(Closure $task): ?SynchronizeSuccess
    {
        $lockAcquired = $this->tryAcquire();

        if (! $lockAcquired) {
            return null;
        }

        try {
            return new SynchronizeSuccess($task());
        } finally {
            $this->release();
        }
    }

    public function trySynchronizeWithTimeout(int $seconds, Closure $task): ?SynchronizeSuccess
    {
        $lockAcquired = $this->tryAcquireWithTimeout($seconds);

        if (! $lockAcquired) {
            return null;
        }

        try {
            return new SynchronizeSuccess($task());
        } finally {
            $this->release();
        }
    }
}
