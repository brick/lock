<?php

declare(strict_types=1);

namespace Brick\Lock\Database\Connection;

use Brick\Lock\Database\ConnectionInterface;
use Brick\Lock\LockException;
use PDO;
use PDOException;

/**
 * Wraps a PDO connection.
 */
final readonly class PdoConnection implements ConnectionInterface
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function querySingleValue(string $sql, array $params = []): mixed
    {
        $previousErrorMode = $this->pdo->getAttribute(PDO::ATTR_ERRMODE);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute($params);

            /** @var list<list<scalar|null>> $rows */
            $rows = $statement->fetchAll(PDO::FETCH_NUM);
        } catch (PDOException $e) {
            throw new LockException(sprintf('An error occurred while executing the query "%s": %s', $sql, $e->getMessage()), 0, $e);
        } finally {
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, $previousErrorMode);
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
