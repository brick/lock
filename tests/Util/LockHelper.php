<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util;

use Brick\Lock\Database\ConnectionInterface;
use Brick\Lock\LockDriverInterface;
use Brick\Lock\LockFactory;
use Brick\Lock\LockFactoryInterface;
use Brick\Lock\LockInterface;
use LogicException;

final class LockHelper
{
    public readonly LockFactoryInterface $lockFactory;

    /**
     * @var list<LockInterface>
     */
    private array $lockStack = [];

    public function __construct(
        public readonly LockDriverInterface $lockDriver,
        public readonly ConnectionInterface $connection,
    ) {
        $this->lockFactory = new LockFactory($this->lockDriver);
    }

    public function pushLock(LockInterface $lock): void
    {
        $this->lockStack[] = $lock;
    }

    public function popLock(): LockInterface
    {
        $lock = array_pop($this->lockStack);

        if ($lock === null) {
            throw new LogicException('Lock stack is empty.');
        }

        return $lock;
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
