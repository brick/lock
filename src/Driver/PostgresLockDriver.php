<?php

declare(strict_types=1);

namespace Brick\Lock\Driver;

use Brick\Lock\Database\ConnectionInterface;
use Brick\Lock\Database\QueryException;
use Brick\Lock\Exception\LockAcquireException;
use Brick\Lock\Exception\LockReleaseException;
use Brick\Lock\Internal\PostgresHasher;
use Brick\Lock\LockDriverInterface;
use Override;

/**
 * PostgreSQL driver using pg_advisory_lock().
 *
 * Postgres does not support lock timeouts, so we use polling to emulate these.
 *
 * https://www.postgresql.org/docs/17/functions-admin.html#FUNCTIONS-ADVISORY-LOCKS
 */
final readonly class PostgresLockDriver implements LockDriverInterface
{
    /**
     * @param int $pollIntervalMs The interval in milliseconds between each poll when using tryAcquireWithTimeout().
     *                            Must be positive.
     */
    public function __construct(
        private ConnectionInterface $connection,
        private int $pollIntervalMs = 100,
    ) {
    }

    #[Override]
    public function acquire(string $lockName): void
    {
        try {
            $this->connection->querySingleValue('SELECT pg_advisory_lock(?, ?)', PostgresHasher::hashLockName($lockName));
        } catch (QueryException $e) {
            throw LockAcquireException::forLockName($lockName, 'Error while calling pg_advisory_lock()', $e);
        }
    }

    #[Override]
    public function tryAcquire(string $lockName): bool
    {
        return $this->doTryAcquire($lockName, PostgresHasher::hashLockName($lockName));
    }

    #[Override]
    public function tryAcquireWithTimeout(string $lockName, int $timeoutSeconds): bool
    {
        $startTime = microtime(true);
        $lockHash = PostgresHasher::hashLockName($lockName);

        while (true) {
            $result = $this->doTryAcquire($lockName, $lockHash);

            if ($result === true) {
                return true;
            }

            if (microtime(true) - $startTime >= $timeoutSeconds) {
                return false;
            }

            usleep($this->pollIntervalMs * 1000);
        }
    }

    #[Override]
    public function release(string $lockName): void
    {
        try {
            $result = $this->connection->querySingleValue('SELECT pg_advisory_unlock(?, ?)', PostgresHasher::hashLockName($lockName));
        } catch (QueryException $e) {
            throw LockReleaseException::forLockName($lockName, 'Error while calling pg_advisory_unlock()', $e);
        }

        if ($result === true) {
            return;
        }

        if ($result === false) {
            throw LockReleaseException::forLockName($lockName, 'The lock was not acquired.');
        }

        throw LockReleaseException::forLockName($lockName, sprintf(
            'Unexpected result from pg_advisory_unlock(): %s',
            var_export($result, true),
        ));
    }

    /**
     * @param array{int, int} $lockHash
     *
     * @throws LockAcquireException
     */
    private function doTryAcquire(string $lockName, array $lockHash): bool
    {
        try {
            $result = $this->connection->querySingleValue('SELECT pg_try_advisory_lock(?, ?)', $lockHash);
        } catch (QueryException $e) {
            throw LockAcquireException::forLockName($lockName, 'Error while calling pg_try_advisory_lock()', $e);
        }

        if (is_bool($result)) {
            return $result;
        }

        throw LockAcquireException::forLockName($lockName, sprintf(
            'Unexpected result from pg_try_advisory_lock(): %s',
            var_export($result, true),
        ));
    }
}
