<?php

declare(strict_types=1);

namespace Brick\Lock;

use Closure;

interface LockInterface
{
    /**
     * Acquires the lock, blocking until it is available.
     *
     * @throws LockException If the lock cannot be acquired due to an error. Whether the lock is held after an error is
     *                       undefined.
     */
    public function acquire(): void;

    /**
     * Tries to acquire the lock, non-blocking.
     *
     * If the lock can be acquired immediately, this method returns true and the lock is held.
     * If the lock is currently held by another process, this method returns false and does not hold the lock.
     *
     * @return bool True if the lock was acquired, false if the lock was not acquired.
     *
     * @throws LockException If the lock cannot be acquired due to an error. Whether the lock is held after an error is
     *                       undefined.
     */
    public function tryAcquire(): bool;

    /**
     * Tries to acquire the lock, with a maximum wait time.
     *
     * If the lock can be acquired before the timeout expires, this method returns true and the lock is held.
     * If the lock cannot be acquired before the timeout expires, this method returns false and does not hold the lock.
     *
     * @param int $seconds The maximum time to wait for the lock, in seconds. Must be positive.
     *
     * @return bool True if the lock was acquired, false if the timeout expired before the lock became available.
     *
     * @throws LockException If the lock cannot be acquired due to an error. Whether the lock is held after an error is
     *                       undefined.
     */
    public function tryAcquireWithTimeout(int $seconds): bool;

    /**
     * Releases the lock.
     *
     * @throws LockException If an error occurs while releasing the lock. Whether the lock is still held after an error
     *                       is undefined.
     */
    public function release(): void;

    /**
     * Waits until the lock is available, without acquiring it.
     *
     * This can be used after an unsuccessful tryAcquire() attempt, to wait for the result of the same operation
     * performed by another process.
     *
     * @throws LockException If an error occurs while waiting for the lock. Whether the lock is held after an error is
     *                       undefined.
     */
    public function wait(): void;

    /**
     * Waits until the lock is available, or the timeout expires.
     *
     * This method does not acquire the lock.
     *
     * @param int $seconds The timeout in seconds.
     *
     * @return bool True if the lock was available, false if the timeout expired before the lock became available.
     */
    public function tryWaitWithTimeout(int $seconds): bool;

    /**
     * Executes the given task while holding the lock.
     *
     * Once the lock is acquired, the closure is executed, and its return value is returned as is.
     *
     * If the closure throws an exception, the lock is released and the exception bubbles up.
     *
     * This method is blocking and will wait for the lock to become available.
     *
     * @template T
     *
     * @param Closure(): T $task
     *
     * @return T The return value of the closure.
     *
     * @throws LockException If the lock cannot be acquired or released due to an error. Whether the lock is held after
     *                       an error is undefined.
     */
    public function synchronize(Closure $task): mixed;

    /**
     * Executes the given task while holding the lock, non-blocking.
     *
     * If the lock is available immediately, it is acquired, the closure is executed, and its return value is
     * returned wrapped in a SynchronizeSuccess object. If the lock is currently held by another process, this method
     * returns null.
     *
     * If the closure throws an exception, the lock is released and the exception bubbles up.
     *
     * @template T
     *
     * @param Closure(): T $task
     *
     * @return SynchronizeSuccess<T>|null The return value of the closure wrapped in a SynchronizeSuccess object,
     *                                    or null if the lock could not be acquired.
     *
     * @throws LockException If the lock cannot be acquired or released due to an error. Whether the lock is held after
     *                       an error is undefined.
     */
    public function trySynchronize(Closure $task): ?SynchronizeSuccess;

    /**
     * Executes the given task while holding the lock, with a maximum wait time.
     *
     * If the lock is successfully acquired before the timeout expires, the closure is executed, and its return value is
     * returned wrapped in a SynchronizeSuccess object. If the lock cannot be acquired before the timeout expires, this
     * method returns null.
     *
     * If the closure throws an exception, the lock is released and the exception bubbles up.
     *
     * @template T
     *
     * @param Closure(): T $task
     *
     * @return SynchronizeSuccess<T>|null The return value of the closure wrapped in a SynchronizeSuccess object,
     *                                     or null if the lock could not be acquired.
     *
     * @throws LockException If the lock cannot be acquired or released due to an error. Whether the lock is held after
     *                       an error is undefined.
     */
    public function trySynchronizeWithTimeout(int $seconds, Closure $task): ?SynchronizeSuccess;
}
