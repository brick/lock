<?php


declare(strict_types=1);

namespace Brick\Lock\Tests;

use Brick\Lock\Internal\PostgresHasher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PostgresHasherTest extends TestCase
{
    #[DataProvider('provideHashLockName')]
    public function testHashLockName(string $lockName, array $expected): void
    {
        self::assertSame($expected, PostgresHasher::hashLockName($lockName));
    }

    public static function provideHashLockName(): array
    {
        return [
            ['foo', [200198069, -364965925]],
            ['bar', [1657648898, 267985125]],
            ['baz', [-1142333278, 1587745234]],
        ];
    }

    #[DataProvider('provideUnpackSigned32bit')]
    public function testUnpackSigned32bit(string $hex, int $expected): void
    {
        self::assertSame($expected, PostgresHasher::unpackSigned32bit(hex2bin($hex)));
    }

    public static function provideUnpackSigned32bit(): array
    {
        return [
            ['00000000', 0],
            ['00000001', 1],
            ['7ffffffe', 2147483646],
            ['7fffffff', 2147483647],
            ['80000000', -2147483648],
            ['80000001', -2147483647],
            ['fffffffe', -2],
            ['ffffffff', -1],
        ];
    }
}
