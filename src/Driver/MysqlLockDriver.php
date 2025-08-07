<?php

declare(strict_types=1);

namespace Brick\Lock\Driver;

use Brick\Lock\LockDriverInterface;
use Brick\Lock\LockException;
use Doctrine\DBAL;

/**
 * MySQL driver using GET_LOCK().
 *
 * https://dev.mysql.com/doc/refman/8.4/en/locking-functions.html#function_get-lock
 */
final readonly class MysqlLockDriver implements LockDriverInterface
{
    public function __construct(
        private DBAL\Connection $connection,
    ) {
    }

    public function acquire(string $lockName): void
    {
        $lockAcquired = $this->doAcquire($lockName, timeoutSeconds: -1);

        if (! $lockAcquired) {
            throw new LockException('Got false from GET_LOCK() with infinite timeout, which should not happen.');
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
            $statement = $this->connection->prepare('SELECT RELEASE_LOCK(?)');
            $statement->bindValue(1, $hashedName);

            $result = $statement->executeQuery()->fetchOne();
        } catch (DBAL\Exception $e) {
            throw new LockException(sprintf(
                'An error occurred while executing RELEASE_LOCK(): %s',
                $e->getMessage(),
            ), 0, $e);
        }

        if ($result === 1 || $result === '1') {
            return; // lock was released successfully
        }

        if ($result === 0 || $result === '0') {
            throw new LockException(sprintf('Cannot release lock with name "%s": the lock exists, but was not established by this thread.', $lockName));
        }

        if ($result === null) {
            throw new LockException(sprintf('Cannot release lock with name "%s": the lock does not exist.', $lockName));
        }

        throw new LockException(sprintf(
            'Unexpected result from RELEASE_LOCK(): %s',
            var_export($result, true),
        ));
    }

    /**
     * Returns true if the lock was successfully acquired, or false if non-blocking and the lock is already held by
     * another thread.
     *
     * @throws LockException
     */
    private function doAcquire(string $lockName, int $timeoutSeconds): bool
    {
        $hashedName = $this->hashLockName($lockName);

        try {
            $statement = $this->connection->prepare('SELECT GET_LOCK(?, ?)');
            $statement->bindValue(1, $hashedName);
            $statement->bindValue(2, $timeoutSeconds);

            $result = $statement->executeQuery()->fetchOne();
        } catch (DBAL\Exception $e) {
            throw new LockException(sprintf(
                'An error occurred while executing GET_LOCK(): %s',
                $e->getMessage(),
            ), 0, $e);
        }

        return match ($result) {
            0, '0' => false,
            1, '1' => true,
            null => throw new LockException(
                "MySQL's GET_LOCK() returned NULL, which indicates an error such as running out of memory, " .
                'or the thread was killed with mysqladmin kill.',
            ),
            default => throw new LockException(sprintf(
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
