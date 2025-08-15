<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util\Command;

use Brick\Lock\Tests\Util\CommandInterface;
use Brick\Lock\Tests\Util\LockHelper;

final readonly class TryWaitWithTimeout implements CommandInterface
{
    /**
     * @param non-empty-list<string> $lockNames
     */
    public function __construct(
        public array $lockNames,
        public int $timeoutSeconds,
    ) {
    }

    public function execute(LockHelper $helper): void
    {
        if (count($this->lockNames) === 1) {
            $lock = $helper->lockFactory->createLock($this->lockNames[0]);
        } else {
            $lock = $helper->lockFactory->createMultiLock($this->lockNames);
        }

        $helper->writeWaitResult(
            $lock->tryWaitWithTimeout($this->timeoutSeconds)
        );
    }
}
