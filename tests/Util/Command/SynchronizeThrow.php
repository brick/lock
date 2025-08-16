<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util\Command;

use Brick\Lock\Tests\Util\CommandInterface;
use Brick\Lock\Tests\Util\LockContext;
use Exception;

final readonly class SynchronizeThrow implements CommandInterface
{
    public function __construct(
        public int $lockIndex,
        public int $taskDurationSeconds,
        public string $taskExceptionMessage,
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

                throw new Exception($this->taskExceptionMessage);
            });
        } catch (Exception $e) {
            $exception = $e;
        }

        /** @phpstan-ignore nullsafe.neverNull */
        $context->writeSyncResult(true, $returnValue, $exception?->getMessage());
    }
}
