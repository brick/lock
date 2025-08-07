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

    public function trySynchronize(Closure $task): SynchronizeResult
    {
        $lockAcquired = $this->tryAcquire();

        if (! $lockAcquired) {
            /** @phpstan-ignore return.type */
            return SynchronizeResult::lockFailure();
        }

        try {
            /** @phpstan-ignore return.type */
            return SynchronizeResult::lockSuccess($task());
        } finally {
            $this->release();
        }
    }

    public function trySynchronizeWithTimeout(int $seconds, Closure $task): SynchronizeResult
    {
        $lockAcquired = $this->tryAcquireWithTimeout($seconds);

        if (! $lockAcquired) {
            /** @phpstan-ignore return.type */
            return SynchronizeResult::lockFailure();
        }

        try {
            /** @phpstan-ignore return.type */
            return SynchronizeResult::lockSuccess($task());
        } finally {
            $this->release();
        }
    }
}
