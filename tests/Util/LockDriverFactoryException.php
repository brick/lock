<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util;

use RuntimeException;

final class LockDriverFactoryException extends RuntimeException
{
    /**
     * @param string[] $errorMessages
     */
    public function __construct(
        public readonly array $errorMessages,
    ) {
        parent::__construct(implode(PHP_EOL, $errorMessages));
    }
}
