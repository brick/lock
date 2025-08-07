<?php

declare(strict_types=1);

namespace Brick\Lock;

interface LockDriverInterface
{
    public function acquire(string $lockName): void;

    public function tryAcquire(string $lockName): bool;

    /**
     * @param int<1, max> $timeoutSeconds
     */
    public function tryAcquireWithTimeout(string $lockName, int $timeoutSeconds): bool;

    public function release(string $lockName): void;
}
