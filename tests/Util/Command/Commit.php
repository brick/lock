<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util\Command;

use Brick\Lock\Tests\Util\CommandInterface;
use Brick\Lock\Tests\Util\LockHelper;

final readonly class Commit implements CommandInterface
{
    public function execute(LockHelper $helper): void
    {
        $helper->connection->commit();
        $helper->write('COMMIT');
    }
}
