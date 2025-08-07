<?php

declare(strict_types=1);

namespace Brick\Lock\Database\Connection;

use Brick\Lock\Database\ConnectionInterface;
use Brick\Lock\LockException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

/**
 * Wraps a Doctrine DBAL connection.
 */
final readonly class DoctrineConnection implements ConnectionInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function querySingleValue(string $sql, array $params = []): mixed
    {
        try {
            $rows = $this->connection->fetchAllNumeric($sql, $params);
        } catch (Exception $e) {
            throw new LockException(sprintf('An error occurred while executing the query "%s": %s', $sql, $e->getMessage()), 0, $e);
        }

        if (count($rows) !== 1) {
            throw new LockException(sprintf('Query "%s" returned %d rows, expected 1.', $sql, count($rows)));
        }

        $columns = $rows[0];

        if (count($columns) !== 1) {
            throw new LockException(sprintf('Query "%s" returned %d columns, expected 1.', $sql, count($columns)));
        }

        /** @var scalar|null */
        return $columns[0];
    }
}
