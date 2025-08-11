<?php

declare(strict_types=1);

namespace Brick\Lock\Database\Connection;

use Brick\Lock\Database\ConnectionInterface;
use Brick\Lock\Database\QueryException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;

/**
 * Wraps a Doctrine DBAL connection.
 */
final readonly class DoctrineConnection implements ConnectionInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    #[Override]
    public function querySingleValue(string $sql, array $params = []): mixed
    {
        try {
            $rows = $this->connection->fetchAllNumeric($sql, $params);
        } catch (Exception $e) {
            throw new QueryException(sprintf('An error occurred while executing the query "%s"', $sql), $e);
        }

        if (count($rows) !== 1) {
            throw new QueryException(sprintf('Query "%s" returned %d rows, expected 1.', $sql, count($rows)));
        }

        $columns = $rows[0];

        if (count($columns) !== 1) {
            throw new QueryException(sprintf('Query "%s" returned %d columns, expected 1.', $sql, count($columns)));
        }

        /** @var scalar|null */
        return $columns[0];
    }
}
