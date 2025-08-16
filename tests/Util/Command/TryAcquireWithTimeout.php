<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util\Command;

use Brick\Lock\Tests\Util\CommandInterface;
use Brick\Lock\Tests\Util\LockContext;

final readonly class TryAcquireWithTimeout implements CommandInterface
{
    public function __construct(
        public int $lockIndex,
        public int $timeoutSeconds,
    ) {
    }

    public function execute(LockContext $context): void
    {
        $lock = $context->getLock($this->lockIndex);
        $acquired = $lock->tryAcquireWithTimeout($this->timeoutSeconds);
        $context->writeAcquireResult($acquired);
    }
}
