<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util\Command;

use Brick\Lock\Tests\Util\CommandInterface;
use Brick\Lock\Tests\Util\LockContext;
use Exception;

final readonly class SynchronizeReturn implements CommandInterface
{
    public function __construct(
        public int $lockIndex,
        public int $taskDurationSeconds,
        public string $taskReturnValue,
    ) {
    }

    public function execute(LockContext $context): void
    {
        $lock = $context->getLock($this->lockIndex);

        $returnValue = null;
        $exception = null;

        try {
            $returnValue = $lock->synchronize(function() {
                sleep($this->taskDurationSeconds);

                return $this->taskReturnValue;
            });
        } catch (Exception $e) {
            $exception = $e;
        }

        $context->writeSyncResult(true, $returnValue, $exception?->getMessage());
    }
}
