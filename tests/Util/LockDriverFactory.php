<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util;

use Brick\Lock\Database\Connection\DoctrineConnection;
use Brick\Lock\Database\Connection\PdoConnection;
use Brick\Lock\Driver\MysqlLockDriver;
use Brick\Lock\Driver\PostgresLockDriver;
use Closure;
use Doctrine\DBAL\DriverManager;
use PDO;

/**
 * Creates a lock driver from environment variables.
 */
final readonly class LockDriverFactory
{
    /**
     * @throws LockDriverFactoryException
     */
    public static function getDriver(): LockDriverWithInfo
    {
        $driver = self::getOptionalEnv('DRIVER');

        $factories = self::getFactories();
        $availableDrivers = array_keys($factories);

        if ($driver === null) {
            throw new LockDriverFactoryException([
                'Running tests requires a lock driver to be set.',
                'Use: DRIVER={driver} vendor/bin/phpunit',
                'Available drivers: ' . implode(', ', $availableDrivers),
            ]);
        }

        if (isset($factories[$driver])) {
            return $factories[$driver]();
        }

        throw new LockDriverFactoryException([
            'Unknown driver: ', $driver,
            'Available drivers: ' . implode(', ', $availableDrivers),
        ]);
    }

    /**
     * @return array<string, Closure(): LockDriverWithInfo>
     */
    private static function getFactories(): array
    {
        return [
            'mysql_pdo' => self::createMysqlPdoDriver(...),
            'mysql_doctrine' => self::createMysqlDoctrineDriver(...),
            'mariadb_pdo' => self::createMariadbPdoDriver(...),
            'mariadb_doctrine' => self::createMariadbDoctrineDriver(...),
            'postgres_pdo' => self::createPostgresPdoDriver(...),
            'postgres_doctrine' => self::createPostgresDoctrineDriver(...),
        ];
    }

    /**
     * @throws LockDriverFactoryException
     */
    private static function createMysqlPdoDriver(): LockDriverWithInfo
    {
        $emulatePrepares = self::getPdoEmulatePreparesEnv();
        $errmode = self::getPdoErrmodeEnv();

        $host = self::getRequiredEnv('MYSQL_HOST');
        $port = self::getOptionalEnvOrDefault('MYSQL_PORT', '3306');
        $username = self::getRequiredEnv('MYSQL_USER');
        $password = self::getRequiredEnv('MYSQL_PASSWORD');

        $dsn = sprintf('mysql:host=%s;port=%d', $host, $port);
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => $errmode,
            PDO::ATTR_EMULATE_PREPARES => $emulatePrepares,
        ]);

        $connection = new PdoConnection($pdo);
        $driver = new MysqlLockDriver($connection, isMariadb: false);

        $serverVersion = $connection->querySingleValue('SELECT VERSION()');

        return new LockDriverWithInfo($driver, [
            'Using MysqlLockDriver through PdoConnection',
            'MySQL server version: ' . $serverVersion,
        ]);
    }

    /**
     * @throws LockDriverFactoryException
     */
    private static function createMysqlDoctrineDriver(): LockDriverWithInfo
    {
        $host = self::getRequiredEnv('MYSQL_HOST');
        $port = self::getOptionalEnvOrDefault('MYSQL_PORT', '3306');
        $username = self::getRequiredEnv('MYSQL_USER');
        $password = self::getRequiredEnv('MYSQL_PASSWORD');

        $connection = DriverManager::getConnection([
            'user' => $username,
            'password' => $password,
            'host' => $host,
            'port' => (int) $port,
            'driver' => 'pdo_mysql',
        ]);

        $connection = new DoctrineConnection($connection);
        $driver = new MysqlLockDriver($connection, isMariadb: false);

        $serverVersion = $connection->querySingleValue('SELECT VERSION()');

        return new LockDriverWithInfo($driver, [
            'Using MysqlLockDriver through DoctrineConnection',
            'MySQL server version: ' . $serverVersion,
        ]);
    }

    /**
     * @throws LockDriverFactoryException
     */
    private static function createMariadbPdoDriver(): LockDriverWithInfo
    {
        $emulatePrepares = self::getPdoEmulatePreparesEnv();
        $errmode = self::getPdoErrmodeEnv();

        $host = self::getRequiredEnv('MARIADB_HOST');
        $port = self::getOptionalEnvOrDefault('MARIADB_PORT', '3306');
        $username = self::getRequiredEnv('MARIADB_USER');
        $password = self::getRequiredEnv('MARIADB_PASSWORD');

        $dsn = sprintf('mysql:host=%s;port=%d', $host, $port);
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => $errmode,
            PDO::ATTR_EMULATE_PREPARES => $emulatePrepares,
        ]);

        $connection = new PdoConnection($pdo);
        $driver = new MysqlLockDriver($connection, isMariadb: true);

        $serverVersion = $connection->querySingleValue('SELECT VERSION()');

        return new LockDriverWithInfo($driver, [
            'Using MysqlLockDriver through PdoConnection',
            'MariaDB server version: ' . $serverVersion,
        ]);
    }

    /**
     * @throws LockDriverFactoryException
     */
    private static function createMariadbDoctrineDriver(): LockDriverWithInfo
    {
        $host = self::getRequiredEnv('MARIADB_HOST');
        $port = self::getOptionalEnvOrDefault('MARIADB_PORT', '3306');
        $username = self::getRequiredEnv('MARIADB_USER');
        $password = self::getRequiredEnv('MARIADB_PASSWORD');

        $connection = DriverManager::getConnection([
            'user' => $username,
            'password' => $password,
            'host' => $host,
            'port' => (int) $port,
            'driver' => 'pdo_mysql',
        ]);

        $connection = new DoctrineConnection($connection);
        $driver = new MysqlLockDriver($connection, isMariadb: true);

        $serverVersion = $connection->querySingleValue('SELECT VERSION()');

        return new LockDriverWithInfo($driver, [
            'Using MysqlLockDriver through DoctrineConnection',
            'MariaDB server version: ' . $serverVersion,
        ]);
    }

    /**
     * @throws LockDriverFactoryException
     */
    private static function createPostgresPdoDriver(): LockDriverWithInfo
    {
        $emulatePrepares = self::getPdoEmulatePreparesEnv();
        $errmode = self::getPdoErrmodeEnv();

        $host = self::getRequiredEnv('POSTGRES_HOST');
        $port = self::getOptionalEnvOrDefault('POSTGRES_PORT', '5432');
        $username = self::getRequiredEnv('POSTGRES_USER');
        $password = self::getRequiredEnv('POSTGRES_PASSWORD');

        $dsn = sprintf('pgsql:host=%s;port=%d', $host, $port);
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => $errmode,
            PDO::ATTR_EMULATE_PREPARES => $emulatePrepares,
        ]);

        $connection = new PdoConnection($pdo);
        $driver = new PostgresLockDriver($connection);

        $serverVersion = $connection->querySingleValue('SELECT version()');

        return new LockDriverWithInfo($driver, [
            'Using PostgresLockDriver through PdoConnection',
            'PostgreSQL server version: ' . $serverVersion,
        ]);
    }

    /**
     * @throws LockDriverFactoryException
     */
    private static function createPostgresDoctrineDriver(): LockDriverWithInfo
    {
        $host = self::getRequiredEnv('POSTGRES_HOST');
        $port = self::getOptionalEnvOrDefault('POSTGRES_PORT', '5432');
        $username = self::getRequiredEnv('POSTGRES_USER');
        $password = self::getRequiredEnv('POSTGRES_PASSWORD');

        $connection = DriverManager::getConnection([
            'user' => $username,
            'password' => $password,
            'host' => $host,
            'port' => (int) $port,
            'driver' => 'pdo_pgsql',
        ]);

        $connection = new DoctrineConnection($connection);
        $driver = new PostgresLockDriver($connection);

        $serverVersion = $connection->querySingleValue('SELECT version()');

        return new LockDriverWithInfo($driver, [
            'Using PostgresLockDriver through DoctrineConnection',
            'PostgreSQL server version: ' . $serverVersion,
        ]);
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

    /**
     * @throws LockDriverFactoryException
     */
    private static function getRequiredEnv(string $name): string
    {
        $value = getenv($name);

        if ($value === false) {
            throw new LockDriverFactoryException([
                'Missing environment variable: ' . $name,
            ]);
        }

        return $value;
    }

    /**
     * @throws LockDriverFactoryException
     */
    private static function getPdoEmulatePreparesEnv() : bool
    {
        $emulatePrepares = self::getOptionalEnv('PDO_EMULATE_PREPARES') ?? 'OFF';

        return match ($emulatePrepares) {
            'ON' => true,
            'OFF' => false,
            default => throw new LockDriverFactoryException([
                'PDO_EMULATE_PREPARES must be ON or OFF, got: ' . $emulatePrepares,
            ])
        };
    }

    /**
     * @return PDO::ERRMODE_*
     *
     * @throws LockDriverFactoryException
     */
    private static function getPdoErrmodeEnv() : int
    {
        $errmode = self::getOptionalEnv('PDO_ERRMODE') ?? 'EXCEPTION';

        return match ($errmode) {
            'SILENT' => PDO::ERRMODE_SILENT,
            'WARNING' => PDO::ERRMODE_WARNING,
            'EXCEPTION' => PDO::ERRMODE_EXCEPTION,
            default => throw new LockDriverFactoryException([
                'PDO_ERRMODE must be SILENT, WARNING, or EXCEPTION, got: ' . $errmode,
            ])
        };
    }
}
