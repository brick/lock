<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util\Command;

use Brick\Lock\Tests\Util\CommandInterface;
use Brick\Lock\Tests\Util\LockHelper;
use Exception;

final readonly class SynchronizeThrow implements CommandInterface
{
    /**
     * @param non-empty-list<string> $lockNames
     */
    public function __construct(
        public array $lockNames,
        public int $taskDurationSeconds,
        public string $taskExceptionMessage,
    ) {
    }

    public function execute(LockHelper $helper): void
    {
        if (count($this->lockNames) === 1) {
            $lock = $helper->lockFactory->createLock($this->lockNames[0]);
        } else {
            $lock = $helper->lockFactory->createMultiLock($this->lockNames);
        }

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
        $helper->writeSyncResult(true, $returnValue, $exception?->getMessage());
    }
}
