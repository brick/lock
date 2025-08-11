<?php

declare(strict_types=1);

namespace Brick\Lock\Exception;

use Throwable;

/**
 * Exception thrown when a lock cannot be waited for due to an error.
 * The state of the lock is undefined after such an exception.
 */
final class LockWaitException extends LockException
{
    public static function wrap(Throwable $previous): self
    {
        return new self(sprintf('Cannot wait for lock: %s', $previous->getMessage()), $previous);
    }
}
