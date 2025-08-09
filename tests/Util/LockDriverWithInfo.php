<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util;

use Brick\Lock\LockDriverInterface;

class LockDriverWithInfo
{
    /**
     * @param string[] $infoMessages
     */
    public function __construct(
        public LockDriverInterface $lockDriver,
        public array $infoMessages = [],
    ) {
    }
}
