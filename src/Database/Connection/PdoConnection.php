<?php

declare(strict_types=1);

namespace Brick\Lock\Database\Connection;

use Brick\Lock\Database\ConnectionInterface;
use Brick\Lock\Database\QueryException;
use Override;
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

    #[Override]
    public function querySingleValue(string $sql, array $params = []): mixed
    {
        /** @phpstan-ignore missingType.checkedException */
        $previousErrorMode = $this->pdo->getAttribute(PDO::ATTR_ERRMODE);

        /** @phpstan-ignore missingType.checkedException */
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute($params);

            /** @var list<list<scalar|null>> $rows */
            $rows = $statement->fetchAll(PDO::FETCH_NUM);
        } catch (PDOException $e) {
            throw new QueryException(sprintf('An error occurred while executing the query "%s"', $sql), $e);
        } finally {
            /** @phpstan-ignore missingType.checkedException */
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, $previousErrorMode);
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

    #[Override]
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    #[Override]
    public function commit(): void
    {
        $this->pdo->commit();
    }

    #[Override]
    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }
}
