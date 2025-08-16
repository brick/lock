<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util\Command;

use Brick\Lock\Tests\Util\CommandInterface;
use Brick\Lock\Tests\Util\LockContext;

final readonly class Acquire implements CommandInterface
{
    public function __construct(
        public int $lockIndex,
    ) {
    }

    public function execute(LockContext $context): void
    {
        $lock = $context->getLock($this->lockIndex);
        $lock->acquire();
        $context->writeAcquireResult(true);
    }
}
