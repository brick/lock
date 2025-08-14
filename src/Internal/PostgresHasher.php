<?php

declare(strict_types=1);

namespace Brick\Lock\Internal;

final class PostgresHasher
{
    /**
     * Returns a pair of 32-bit integers that can be used as PostgreSQL advisory lock keys.
     *
     * Although Postgres supports using a single 64-bit key, we use 32-bit keys for portability on 32-bit systems.
     *
     * @return array{int, int}
     */
    public static function hashLockName(string $lockName): array
    {
        $hash = sha1($lockName, true);

        return [
            self::unpackSigned32bit(substr($hash, 0, 4)),
            self::unpackSigned32bit(substr($hash, 4, 4)),
        ];
    }

    public static function unpackSigned32bit(string $binary): int
    {
        /** @var array{1: int} $parts */
        $parts = unpack('N', $binary); // unsigned long (always 32 bit, big endian byte order)

        $value = $parts[1];

        if (PHP_INT_SIZE === 4) {
            // already signed on 32-bit systems, even though it's documented as unsigned!
            return $value;
        }

        // unsigned on 64-bit systems, convert to signed
        if ($value > 0x7FFFFFFF) {
            $value -= 0x100000000;
        }

        return $value;
    }
}
