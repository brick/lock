<?php

declare(strict_types=1);

namespace Brick\Lock\Database;

use Throwable;

/**
 * Interface for database connections.
 * Transactions are not used by the library itself, but used in tests to ensure that they do not interfere with locks.
 */
interface ConnectionInterface
{
    /**
     * Executes a SQL query and returns the first column of the first row in the result set.
     *
     * @param list<scalar|null> $params
     *
     * @return scalar|null
     *
     * @throws QueryException If the query fails or does not return exactly one row and one column.
     */
    public function querySingleValue(string $sql, array $params = []): mixed;

    /**
     * Begins a transaction. Only used in tests. Native connection exceptions may bubble up.
     *
     * @throws Throwable
     */
    public function beginTransaction(): void;

    /**
     * Commits the current transaction. Only used in tests. Native connection exceptions may bubble up.
     *
     * @throws Throwable
     */
    public function commit(): void;

    /**
     * Rolls back the current transaction. Only used in tests. Native connection exceptions may bubble up.
     *
     * @throws Throwable
     */
    public function rollBack(): void;
}
