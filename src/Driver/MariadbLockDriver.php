<?php

declare(strict_types=1);

namespace Brick\Lock\Driver;

use Override;

/**
 * MariaDB driver using GET_LOCK().
 *
 * https://mariadb.com/docs/server/reference/sql-functions/secondary-functions/miscellaneous-functions/get_lock
 */
final readonly class MariadbLockDriver extends AbstractMysqlLockDriver
{
    #[Override]
    protected function getInfiniteTimeout(): int
    {
        // MariaDB does not support infinite timeouts, so we use a very large value that's still a valid 32-bit signed
        // integer, just to be on the safe side. 2 billion seconds is about 63 years.
        return 2_000_000_000;
    }
}
