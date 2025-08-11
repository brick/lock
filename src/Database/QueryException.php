<?php

declare(strict_types=1);

namespace Brick\Lock\Database;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when a database query fails.
 */
final class QueryException extends RuntimeException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }
}
