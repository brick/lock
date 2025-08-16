<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util\Command;

use Brick\Lock\Exception\LockAcquireException;
use Brick\Lock\Tests\Util\CommandInterface;
use Brick\Lock\Tests\Util\LockContext;
use Closure;
use Exception;

final readonly class TrySynchronizeWithTimeout implements CommandInterface
{
    /**
     * @param Closure(): string $task
     */
    public function __construct(
        public int $lockIndex,
        public int $timeoutSeconds,
        public Closure $task,
    ) {
    }

    public function execute(LockContext $context): void
    {
        $lock = $context->getLock($this->lockIndex);

        $returnValue = null;
        $exception = null;

        try {
            $synchronizeSuccess = $lock->trySynchronizeWithTimeout($this->timeoutSeconds, $this->task);
            $returnValue = $synchronizeSuccess?->returnValue;
            $isLockSuccess = $synchronizeSuccess !== null;
        } catch (Exception $e) {
            $exception = $e;
            $isLockSuccess = ! $e instanceof LockAcquireException;
        }

        $context->writeSyncResult($isLockSuccess, $returnValue, $exception?->getMessage());
    }
}
