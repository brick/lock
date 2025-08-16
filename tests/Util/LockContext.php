<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util;

use Brick\Lock\Database\ConnectionInterface;
use Brick\Lock\LockDriverInterface;
use Brick\Lock\LockFactory;
use Brick\Lock\LockFactoryInterface;
use Brick\Lock\LockInterface;
use LogicException;

/**
 * Context for CommandInterface::execute().
 *
 * - Provides access to the lock driver, the connection and the lock factory;
 * - Stores lock instances created by the lock factory;
 * - Provides convenience methods to write to STDOUT.
 */
final class LockContext
{
    public readonly LockFactoryInterface $lockFactory;

    /**
     * @var list<LockInterface>
     */
    private array $locks = [];

    public function __construct(
        public readonly LockDriverInterface $lockDriver,
        public readonly ConnectionInterface $connection,
    ) {
        $this->lockFactory = new LockFactory($this->lockDriver);
    }

    /**
     * Adds a lock and returns its index.
     */
    public function addLock(LockInterface $lock): int
    {
        $this->locks[] = $lock;

        return count($this->locks) - 1;
    }

    public function getLock(int $index): LockInterface
    {
        if ($index >= count($this->locks)) {
            throw new LogicException('Lock not found.');
        }

        return $this->locks[$index];
    }

    public function write(string $message): void
    {
        fwrite(STDOUT, "$message\n");
        fflush(STDOUT);
    }

    public function writeAcquireResult(bool $isAcquired): void
    {
        $this->write($isAcquired ? 'ACQUIRED' : 'NOT_ACQUIRED');
    }

    public function writeWaitResult(bool $isSuccess): void
    {
        $this->write($isSuccess ? 'WAIT_SUCCESS' : 'WAIT_FAILURE');
    }

    public function writeSyncResult(
        bool $isLockSuccess,
        ?string $returnValue,
        ?string $exceptionMessage,
    ): void {
        $writeMessage = $isLockSuccess ? 'SYNC_LOCK_SUCCESS' : 'SYNC_LOCK_FAILURE';

        if ($returnValue !== null) {
            $writeMessage .= ';RETURN:' . $returnValue;
        }

        if ($exceptionMessage !== null) {
            $writeMessage .= ';EXCEPTION:' . $exceptionMessage;
        }

        $this->write($writeMessage);
    }
}
