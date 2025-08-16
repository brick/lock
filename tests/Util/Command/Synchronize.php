<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util\Command;

use Brick\Lock\Tests\Util\CommandInterface;
use Brick\Lock\Tests\Util\LockContext;
use Closure;
use Exception;

final readonly class Synchronize implements CommandInterface
{
    /**
     * @param Closure(): string $task
     */
    public function __construct(
        public int $lockIndex,
        public Closure $task,
    ) {
    }

    public function execute(LockContext $context): void
    {
        $lock = $context->getLock($this->lockIndex);

        $returnValue = null;
        $exception = null;

        try {
            $returnValue = $lock->synchronize($this->task);
        } catch (Exception $e) {
            $exception = $e;
        }

        $context->writeSyncResult(true, $returnValue, $exception?->getMessage());
    }
}
