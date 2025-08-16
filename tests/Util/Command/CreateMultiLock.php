<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util\Command;

use Brick\Lock\Tests\Util\CommandInterface;
use Brick\Lock\Tests\Util\LockContext;

final readonly class CreateMultiLock implements CommandInterface
{
    /**
     * @param non-empty-list<string> $lockNames
     */
    public function __construct(
        public array $lockNames,
    ) {
    }

    public function execute(LockContext $context): void
    {
        $lock = $context->lockFactory->createMultiLock($this->lockNames);
        $lockIndex = $context->addLock($lock);
        $context->write('CREATED:' . $lockIndex);
    }
}
