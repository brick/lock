<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util;

use Brick\Lock\Database\Connection\PdoConnection;
use Brick\Lock\Driver\MysqlLockDriver;
use Closure;
use PDO;

/**
 * Creates a lock driver from environment variables.
 */
final readonly class LockDriverFactory
{
    public static function getDriver(): LockDriverFactorySuccess|LockDriverFactoryFailure
    {
        $driver = self::getOptionalEnv('DRIVER');

        $factories = self::getFactories();
        $availableDrivers = array_keys($factories);

        if ($driver === null) {
            return new LockDriverFactoryFailure([
                'Running tests requires a lock driver to be set.',
                'Use: DRIVER={driver} vendor/bin/phpunit',
                'Available drivers: ' . implode(', ', $availableDrivers),
            ]);
        }

        if (isset($factories[$driver])) {
            return $factories[$driver]();
        }

        return new LockDriverFactoryFailure([
            'Unknown driver: ', $driver,
            'Available drivers: ' . implode(', ', $availableDrivers),
        ]);
    }

    /**
     * @return array<string, Closure(): LockDriverFactorySuccess|LockDriverFactoryFailure>
     */
    private static function getFactories(): array
    {
        return [
            'mysql-pdo' => self::createMysqlPdoDriver(...),
            'mysql-doctrine' => self::createMysqlDoctrineDriver(...),
            'postgres-pdo' => self::createPostgresPdoDriver(...),
            'postgres-doctrine' => self::createPostgresDoctrineDriver(...),
        ];
    }

    private static function createMysqlPdoDriver(): LockDriverFactorySuccess|LockDriverFactoryFailure
    {
        $emulatePrepares = self::getPdoEmulatePreparesEnv();
        $errmode = self::getPdoErrmodeEnv();

        $host = self::getRequiredEnv('MYSQL_HOST');
        $port = self::getOptionalEnv('MYSQL_PORT');
        $username = self::getRequiredEnv('MYSQL_USER');
        $password = self::getRequiredEnv('MYSQL_PASSWORD');

        $dsn = sprintf('mysql:host=%s;port=%d', $host, $port);
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => $errmode,
            PDO::ATTR_EMULATE_PREPARES => $emulatePrepares,
        ]);

        $connection = new PdoConnection($pdo);
        $driver = new MysqlLockDriver($connection);

        $serverVersion = $connection->querySingleValue('SELECT VERSION()');

        return new LockDriverFactorySuccess($driver, [
            'Using ' .MysqlLockDriver::class,
            'Using ' .PdoConnection::class,
            'MySQL server version: ' . $serverVersion,
        ]);
    }

    private static function createMysqlDoctrineDriver(): LockDriverFactorySuccess|LockDriverFactoryFailure
    {

    }

    private static function createPostgresPdoDriver(): LockDriverFactorySuccess|LockDriverFactoryFailure
    {

    }

    private static function createPostgresDoctrineDriver(): LockDriverFactorySuccess|LockDriverFactoryFailure
    {

    }

    private static function getOptionalEnv(string $name): ?string
    {
        $value = getenv($name);

        return $value === false ? null : $value;
    }

    private static function getOptionalEnvOrDefault(string $name, string $default): string
    {
        $value = getenv($name);

        return $value === false ? $default : $value;
    }

    private static function getRequiredEnv(string $name): string
    {
        $value = getenv($name);

        if ($value === false) {
            echo 'Missing environment variable: ', $name, PHP_EOL;
            exit(1);
        }

        return $value;
    }

    private static function getPdoEmulatePreparesEnv() : bool
    {
        $emulatePrepares = self::getOptionalEnv('PDO_EMULATE_PREPARES') ?? 'OFF';

        return match ($emulatePrepares) {
            'ON' => true,
            'OFF' => false,
        };
    }

    /**
     * @return PDO::ERRMODE_*
     */
    private static function getPdoErrmodeEnv() : int
    {
        $errmode = self::getOptionalEnv('PDO_ERRMODE') ?? 'EXCEPTION';

        return match ($errmode) {
            'SILENT' => PDO::ERRMODE_SILENT,
            'WARNING' => PDO::ERRMODE_WARNING,
            'EXCEPTION' => PDO::ERRMODE_EXCEPTION,
        };
    }
}
