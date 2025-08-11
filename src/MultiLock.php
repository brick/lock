<?php

declare(strict_types=1);

namespace Brick\Lock;

use Brick\Lock\Exception\LockAcquireException;
use Brick\Lock\Exception\LockReleaseException;
use InvalidArgumentException;
use Override;

final readonly class MultiLock extends AbstractLock
{
    /**
     * @var list<string>
     */
    private array $lockNames;

    /**
     * @param string[] $lockNames
     */
    public function __construct(
        private LockDriverInterface $store,
        array                       $lockNames,
    ) {
        // sort lock names to minimize deadlock risk
        sort($lockNames);
        $this->lockNames = $lockNames;
    }

    #[Override]
    public function acquire(): void
    {
        foreach ($this->lockNames as $lockName) {
            $this->store->acquire($lockName);
        }
    }

    #[Override]
    public function tryAcquire(): bool
    {
        $acquiredLockNames = [];

        foreach ($this->lockNames as $lockName) {
            if ($this->store->tryAcquire($lockName)) {
                $acquiredLockNames[] = $lockName;
            } else {
                try {
                    foreach ($acquiredLockNames as $acquiredLockName) {
                        $this->store->release($acquiredLockName);
                    }
                } catch (LockReleaseException $e) {
                    throw LockAcquireException::forMultiLock('Failed to release previously acquired lock', $e);
                }

                return false;
            }
        }

        return true;
    }

    #[Override]
    public function tryAcquireWithTimeout(int $seconds): bool
    {
        if ($seconds <= 0) {
            throw new InvalidArgumentException('Timeout must be a positive integer.');
        }

        $acquiredLockNames = [];
        $startTime = microtime(true);

        foreach ($this->lockNames as $lockName) {
            $elapsedSeconds = (int) (microtime(true) - $startTime); // rounds down
            $secondsLeft = max(0, $seconds - $elapsedSeconds); // zero or more

            $lockAcquired = ($secondsLeft === 0)
                ? $this->store->tryAcquire($lockName)
                : $this->store->tryAcquireWithTimeout($lockName, $secondsLeft);

            if ($lockAcquired) {
                $acquiredLockNames[] = $lockName;
            } else {
                try {
                    foreach ($acquiredLockNames as $acquiredLockName) {
                        $this->store->release($acquiredLockName);
                    }
                } catch (LockReleaseException $e) {
                    throw LockAcquireException::forMultiLock('Failed to release previously acquired lock', $e);
                }

                return false;
            }
        }

        return true;
    }

    #[Override]
    public function release(): void
    {
        foreach ($this->lockNames as $lockName) {
            $this->store->release($lockName);
        }
    }
}
