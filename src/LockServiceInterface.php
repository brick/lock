<?php

declare(strict_types=1);

namespace Brick\Lock;

interface LockServiceInterface
{
    /**
     * Acquires multiple named locks, blocking until all locks are available.
     *
     * If another thread already holds any of the locks, this method will block until all locks become available.
     *
     * @param string[] $names The lock names. Each name should uniquely identify the resource being locked.
     *                        The locks will be acquired in alphabetical order to minimize the risk of deadlocks.
     *
     * @throws LockException If a lock cannot be acquired due to an error. Whether locks are held after an error is
     *                       undefined.
     */
    public function getLocks(array $names): void;

    /**
     * Tries to acquire multiple named locks, with a maximum wait time.
     *
     * If all locks can be acquired before the timeout expires, this method returns true and the locks are held.
     * If all locks cannot be acquired before the timeout expires, it returns false and does not hold any lock.
     *
     * If the timeout is 0 (default), this method is effectively non-blocking, meaning it will return immediately.
     *
     * @param string[] $names   An array of lock names. Each name should uniquely identify the resource being locked.
     * @param int      $timeout The total maximum time to wait for the locks, in seconds. Must be positive or zero.
     *
     * @throws LockException If a lock cannot be acquired due to an error. Whether locks are held after an error is
     *                       undefined.
     */
    public function tryGetLocks(array $names, int $timeout = 0): bool;

    /**
     * Waits until the given named lock is available, or the given timeout expires.
     *
     * This method does not acquire the lock, it only waits for it to become available.
     *
     * @param string $name    The lock name. This arbitrary string should uniquely identify the resource being locked.
     * @param int    $timeout The maximum time to wait for the lock, in seconds. Must be positive or zero.
     *
     * @throws LockException If an error occurs while waiting for the lock.
     */
    public function tryWaitForLock(string $name, int $timeout): void;
}
