<?php

declare(strict_types=1);

namespace Brick\Lock\Exception;

use Throwable;

/**
 * Exception thrown when a lock cannot be acquired due to an error.
 * It is ONLY thrown in case of an error: it is NOT thrown when the lock cannot be acquired because it is already held.
 * The state of the lock is undefined after such an exception.
 */
final class LockAcquireException extends LockException
{
    public static function forLockName(string $lockName, string $errorDescription, ?Throwable $previous = null): self
    {
        $message = sprintf('Cannot acquire lock with name "%s": %s', $lockName, $errorDescription);

        return new self($message, $previous);
    }

    public static function forMultiLock(string $errorDescription, ?Throwable $previous = null): self
    {
        $message = sprintf('Cannot acquire multi lock: %s', $errorDescription);

        return new self($message, $previous);
    }
}
