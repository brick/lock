<?php

declare(strict_types=1);

namespace Brick\Lock\Exception;

use RuntimeException;
use Throwable;

/**
 * Base class for fine-grained lock exceptions.
 */
abstract class LockException extends RuntimeException
{
    final public function __construct(string $message, ?Throwable $previous = null)
    {
        if ($previous !== null) {
            $message .= ': ' . $previous->getMessage();
        }

        parent::__construct($message, previous: $previous);
    }
}
