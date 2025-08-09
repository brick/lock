<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util;

class LockDriverFactoryFailure
{
    /**
     * @param string[] $errorMessages
     */
    public function __construct(
        public array $errorMessages,
    ) {
    }
}
