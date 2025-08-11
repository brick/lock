<?php

declare(strict_types=1);

namespace Brick\Lock\Database;

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
}
