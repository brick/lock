<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util\Command;

use Brick\Lock\Tests\Util\CommandInterface;
use Brick\Lock\Tests\Util\LockHelper;

final readonly class Release implements CommandInterface
{
    public function __construct(
        public ?string $lockName,
    ) {
    }

    public function execute(LockHelper $helper): void
    {
        if ($this->lockName !== null) {
            $lock = $helper->lockFactory->createLock($this->lockName);
        } else {
            $lock = $helper->popLock();
        }

        $lock->release();
        $helper->write('RELEASED');
    }
}
