<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util\Command;

use Brick\Lock\Tests\Util\CommandInterface;
use Brick\Lock\Tests\Util\LockContext;

final readonly class RollBack implements CommandInterface
{
    public function execute(LockContext $context): void
    {
        $context->connection->rollBack();
        $context->write('ROLLBACK');
    }
}
