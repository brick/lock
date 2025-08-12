<?php

declare(strict_types=1);

namespace Brick\Lock\Driver;

use Override;

/**
 * MySQL driver using GET_LOCK().
 *
 * https://dev.mysql.com/doc/refman/8.4/en/locking-functions.html#function_get-lock
 */
final readonly class MysqlLockDriver extends AbstractMysqlLockDriver
{
    #[Override]
    protected function getInfiniteTimeout(): int
    {
        // Any negative value is interpreted as infinite timeout in MySQL.
        return -1;
    }
}
