<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util\Command;

use Brick\Lock\Tests\Util\CommandInterface;
use Brick\Lock\Tests\Util\LockContext;

final readonly class TryWaitWithTimeout implements CommandInterface
{
    public function __construct(
        public int $lockIndex,
        public int $timeoutSeconds,
    ) {
    }

    public function execute(LockContext $context): void
    {
        $lock = $context->getLock($this->lockIndex);
        $success = $lock->tryWaitWithTimeout($this->timeoutSeconds);
        $context->writeWaitResult($success);
    }
}
