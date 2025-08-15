<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util;

interface CommandInterface
{
    public function execute(LockHelper $helper): void;
}
