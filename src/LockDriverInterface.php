<?php

declare(strict_types=1);

namespace Brick\Lock;

use Brick\Lock\Exception\LockAcquireException;
use Brick\Lock\Exception\LockReleaseException;

interface LockDriverInterface
{
    /**
     * @throws LockAcquireException
     */
    public function acquire(string $lockName): void;

    /**
     * @throws LockAcquireException
     */
    public function tryAcquire(string $lockName): bool;

    /**
     * @param int<1, max> $timeoutSeconds
     *
     * @throws LockAcquireException
     */
    public function tryAcquireWithTimeout(string $lockName, int $timeoutSeconds): bool;

    /**
     * @throws LockReleaseException
     */
    public function release(string $lockName): void;
}
