<?php

declare(strict_types=1);

namespace Brick\Lock\Driver;

use Brick\Lock\Database\ConnectionInterface;
use Brick\Lock\Database\QueryException;
use Brick\Lock\Exception\LockAcquireException;
use Brick\Lock\Exception\LockReleaseException;
use Brick\Lock\LockDriverInterface;

/**
 * MySQL driver using GET_LOCK().
 *
 * https://dev.mysql.com/doc/refman/8.4/en/locking-functions.html#function_get-lock
 */
final readonly class MysqlLockDriver implements LockDriverInterface
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {
    }

    public function acquire(string $lockName): void
    {
        $lockAcquired = $this->doAcquire($lockName, timeoutSeconds: -1);

        if (! $lockAcquired) {
            throw LockAcquireException::forLockName($lockName, 'Got false from GET_LOCK() with infinite timeout, which should not happen.');
        }
    }

    public function tryAcquire(string $lockName): bool
    {
        return $this->doAcquire($lockName, timeoutSeconds: 0);
    }

    public function tryAcquireWithTimeout(string $lockName, int $timeoutSeconds): bool
    {
        return $this->doAcquire($lockName, $timeoutSeconds);
    }

    public function release(string $lockName): void
    {
        $hashedName = $this->hashLockName($lockName);

        try {
            $result = $this->connection->querySingleValue('SELECT RELEASE_LOCK(?)', [$hashedName]);
        } catch (QueryException $e) {
            throw LockReleaseException::forLockName($lockName, sprintf('Error while calling RELEASE_LOCK(): %s', $e->getMessage()), $e);
        }

        if ($result === 1 || $result === '1') {
            return; // lock was released successfully
        }

        if ($result === 0 || $result === '0') {
            throw LockReleaseException::forLockName($lockName, 'The lock exists, but was not established by this thread.');
        }

        if ($result === null) {
            throw LockReleaseException::forLockName($lockName, 'The lock does not exist.');
        }

        throw LockReleaseException::forLockName($lockName, sprintf(
            'Unexpected result from RELEASE_LOCK(): %s',
            var_export($result, true),
        ));
    }

    /**
     * Returns true if the lock was successfully acquired, or false if non-blocking and the lock is already held by
     * another thread.
     *
     * @throws LockAcquireException
     */
    private function doAcquire(string $lockName, int $timeoutSeconds): bool
    {
        $hashedName = $this->hashLockName($lockName);

        try {
            $result = $this->connection->querySingleValue('SELECT GET_LOCK(?, ?)', [$hashedName, $timeoutSeconds]);
        } catch (QueryException $e) {
            throw LockAcquireException::forLockName($lockName, sprintf('Error while calling GET_LOCK(): %s', $e->getMessage()), $e);
        }

        return match ($result) {
            0, '0' => false,
            1, '1' => true,
            null => throw LockAcquireException::forLockName(
                $lockName,
                "MySQL's GET_LOCK() returned NULL, which indicates an error such as running out of memory, " .
                'or the thread was killed with mysqladmin kill.',
            ),
            default => throw LockAcquireException::forLockName($lockName, sprintf(
                'Unexpected result from GET_LOCK(): %s',
                var_export($result, true),
            )),
        };
    }

    /**
     * Returns a hashed lock name to ensure that we stay within the MySQL lock name length limit of 64 chars.
     */
    private function hashLockName(string $lockName): string
    {
        return sha1($lockName);
    }
}
