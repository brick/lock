<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util;

use Brick\Lock\Database\ConnectionInterface;
use Brick\Lock\LockDriverInterface;

class LockDriverWithInfo
{
    /**
     * @param string[] $infoMessages
     */
    public function __construct(
        public LockDriverInterface $lockDriver,
        public ConnectionInterface $connection,
        public array $infoMessages = [],
    ) {
    }
}
