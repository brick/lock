<?php

declare(strict_types=1);

namespace Brick\Lock\Exception;

use Throwable;

/**
 * Exception thrown when a lock cannot be released due to an error.
 * The state of the lock is undefined after such an exception.
 */
final class LockReleaseException extends LockException
{
    public static function forLockName(string $lockName, string $errorDescription, ?Throwable $previous = null): self
    {
        $message = sprintf('Cannot release lock with name "%s": %s', $lockName, $errorDescription);

        return new self($message, $previous);
    }
}
