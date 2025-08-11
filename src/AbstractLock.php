<?php

declare(strict_types=1);

namespace Brick\Lock;

use Brick\Lock\Exception\LockAcquireException;
use Brick\Lock\Exception\LockReleaseException;
use Brick\Lock\Exception\LockWaitException;
use Closure;
use Override;

abstract readonly class AbstractLock implements LockInterface
{
    #[Override]
    public function wait(): void
    {
        try {
            $this->acquire();
            $this->release();
        } catch (LockAcquireException|LockReleaseException $e) {
            throw LockWaitException::wrap($e);
        }
    }

    #[Override]
    public function tryWaitWithTimeout(int $seconds): bool
    {
        try {
            if ($this->tryAcquireWithTimeout($seconds)) {
                $this->release();

                return true;
            }
        } catch (LockAcquireException|LockReleaseException $e) {
            throw LockWaitException::wrap($e);
        }

        return false;
    }

    #[Override]
    public function synchronize(Closure $task): mixed
    {
        $this->acquire();

        try {
            return $task();
        } finally {
            $this->release();
        }
    }

    #[Override]
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

    #[Override]
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
