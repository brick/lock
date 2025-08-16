<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util\Command;

use Brick\Lock\Exception\LockAcquireException;
use Brick\Lock\Tests\Util\CommandInterface;
use Brick\Lock\Tests\Util\LockContext;
use Exception;

final readonly class TrySynchronizeWithTimeoutReturn implements CommandInterface
{
    public function __construct(
        public int $lockIndex,
        public int $timeoutSeconds,
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
            $synchronizeSuccess = $lock->trySynchronizeWithTimeout($this->timeoutSeconds, function() {
                sleep($this->taskDurationSeconds);

                return $this->taskReturnValue;
            });
            $returnValue = $synchronizeSuccess?->returnValue;
            $isLockSuccess = $synchronizeSuccess !== null;
        } catch (Exception $e) {
            $exception = $e;
            $isLockSuccess = ! $e instanceof LockAcquireException;
        }

        $context->writeSyncResult($isLockSuccess, $returnValue, $exception?->getMessage());
    }
}
