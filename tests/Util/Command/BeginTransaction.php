<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util\Command;

use Brick\Lock\Tests\Util\CommandInterface;
use Brick\Lock\Tests\Util\LockContext;

final readonly class BeginTransaction implements CommandInterface
{
    public function execute(LockContext $context): void
    {
        $context->connection->beginTransaction();
        $context->write('BEGIN');
    }
}
