<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util\Command;

use Brick\Lock\Tests\Util\CommandInterface;
use Brick\Lock\Tests\Util\LockContext;

final readonly class CreateLock implements CommandInterface
{
    public function __construct(
        public string $lockName,
    ) {
    }

    public function execute(LockContext $context): void
    {
        $lock = $context->lockFactory->createLock($this->lockName);
        $lockIndex = $context->addLock($lock);
        $context->write('CREATED:' . $lockIndex);
    }
}
