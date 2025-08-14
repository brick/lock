# Brick\Lock

<img src="https://raw.githubusercontent.com/brick/brick/master/logo.png" alt="" align="left" height="64">

Advisory locking for PHP applications.

[![Build Status](https://github.com/brick/lock/workflows/CI/badge.svg)](https://github.com/brick/lock/actions)
[![Coverage Status](https://coveralls.io/repos/github/brick/lock/badge.svg?branch=master)](https://coveralls.io/github/brick/lock?branch=master)
[![Latest Stable Version](https://poser.pugx.org/brick/lock/v/stable)](https://packagist.org/packages/brick/lock)
[![Total Downloads](https://poser.pugx.org/brick/lock/downloads)](https://packagist.org/packages/brick/lock)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](http://opensource.org/licenses/MIT)

## Overview

This library provides a simple interface to work with advisory (named) locks for inter-process synchronization.

It works by using a database (MySQL, MariaDB or PostgreSQL) as a backend for locks. This allows the locks to work across multiple processes, and even across different web servers. It uses the native advisory locking functionality of each database (`GET_LOCK()` on MySQL / MariaDB, `pg_advisory_lock()` on PostgreSQL).

Locks are tied to the database connection they were created with, and are automatically released when the connection is closed, which prevents locks from remaining unreleased after a crash or a bug.

Locks are not affected by transactions, so it is safe to use your existing database connection.

## Installation

This library is installable via [Composer](https://getcomposer.org/):

```bash
composer require brick/lock
```

## Requirements

This library requires PHP 8.2 or later.

## Project status & release process

This library is under development and its API may evolve, but it is well-tested and considered ready for production use.

The current releases are numbered `0.x.y`. When a non-breaking change is introduced (adding new methods, optimizing existing code, etc.), `y` is incremented.

**When a breaking change is introduced, a new `0.x` version cycle is always started.**

It is therefore safe to lock your project to a given release cycle, such as `0.1.*`.

If you need to upgrade to a newer release cycle, check the [release history](https://github.com/brick/lock/releases) for a list of changes introduced by each further `0.x.0` version.

## Usage

You first need to instantiate a lock driver. For example, to use MySQL over a PDO connection:

```php
use Brick\Lock\Database\Connection\PdoConnection;
use Brick\Lock\Driver\MysqlLockDriver;

$pdo = new PDO('mysql:host=localhost', 'user', 'password');
$connection = new PdoConnection($pdo);
$driver = new MysqlLockDriver($connection);
```

> [!TIP]
> Available connections: `PdoConnection`, `DoctrineConnection`  
> Available drivers: `MysqlLockDriver`, `MariadbLockDriver`, `PostgresLockDriver`

You can then instantiate the lock factory, which is the entry point to create named locks:

```php
use Brick\Lock\LockFactory;

$lockFactory = new LockFactory($driver);
```

You can now create a lock object using a unique name that identifies the resource you want to lock:

```php
$lock = $lockFactory->createLock('my_lock_name');
```

And use it to acquire a lock:

```php
$lock->acquire();
// ... do some work while the lock is held ...
$lock->release();
```

## The `LockInterface` object

The object returned by `createLock()` implements `LockInterface`, which provides the following methods:

- **`acquire(): void`**

  Acquires the lock, blocking until it is available.

    ```php
    $lock->acquire();
    ```

- **`tryAcquire(): bool`**

  Tries to acquire the lock, non-blocking.

  If the lock can be acquired immediately, this method returns `true` and the lock is held.
  If the lock is currently held by another process, this method returns `false` and does not hold the lock.

    ```php
    if ($lock->tryAcquire()) {
        // the lock is acquired
    } else {
        // the lock is currently held by another process
    }
    ```

- **`tryAcquireWithTimeout(int $seconds): bool`**

  Tries to acquire the lock, with a maximum wait time.

  If the lock can be acquired before the timeout expires, this method returns `true` and the lock is held.
  If the lock cannot be acquired before the timeout expires, this method returns `false` and does not hold the lock.

    ```php
    if ($lock->tryAcquireWithTimeout(10)) {
        // the lock is acquired
    } else {
        // the lock could not be acquired after 10s
    }
    ```

- **`release(): void`**

  Releases the lock.

    ```php
    $lock->release();
    ```

  Attempting to release a lock that is not held throws a `LockReleaseException`.

- **`wait(): void`**

  Waits until the lock is available, without acquiring it.

  This can be used after an unsuccessful `tryAcquire()` attempt, to wait for the result of the same operation performed by another process.

    ```php
    $lock->wait();
    ```

- **`tryWaitWithTimeout(int $seconds): bool`**

  Waits until the lock is available, or the timeout expires.

  This method does not acquire the lock.

    ```php
    if ($lock->tryWaitWithTimeout(10)) {
        // the lock was available before the end of the timeout
    } else {
        // the lock was still not available after 10s
    }
    ```

- **`synchronize<T>(Closure(): T $task): T`**

  Executes the given task while holding the lock.

  Once the lock is acquired, the closure is executed, and its return value is returned as is.
  If the closure throws an exception, the lock is released and the exception bubbles up.
  This method is blocking and will wait for the lock to become available.

    ```php
    $result = $lock->synchronize(function() {
        // ...do some work that requires an exclusive lock...
    
        return 'some value';
    });
    
    // $result === 'some value'
    ```

- **`trySynchronize<T>(Closure(): T $task): SynchronizeSuccess<T>|null`**

  Executes the given task while holding the lock, non-blocking.

  If the lock is available immediately, it is acquired, the closure is executed, and its return value is returned
  wrapped in a `SynchronizeSuccess` object. If the lock is currently held by another process, this method returns `null`.
  If the closure throws an exception, the lock is released and the exception bubbles up.

    ```php
    $result = $lock->trySynchronize(function() {
        // ...do some work that requires an exclusive lock...
    
        return 'some value';
    });
    
    if ($result !== null) {
        // the lock was acquired and the closure was executed
        // $result->returnValue === 'some value'
    } else {
        // the lock was not available
    }
    ```

- **`trySynchronizeWithTimeout<T>(int $seconds, Closure(): T $task): SynchronizeSuccess<T>|null`**

  Executes the given task while holding the lock, with a maximum wait time.

  If the lock is successfully acquired before the timeout expires, the closure is executed, and its return value is
  returned wrapped in a `SynchronizeSuccess` object. If the lock cannot be acquired before the timeout expires, this
  method returns `null`. If the closure throws an exception, the lock is released and the exception bubbles up.

    ```php
    $result = $lock->trySynchronizeWithTimeout(10, function() {
        // ...do some work that requires an exclusive lock...
    
        return 'some value';
    });
    
    if ($result !== null) {
        // the lock was acquired and the closure was executed
        // $result->returnValue === 'some value'
    } else {
        // the lock was still not available after 10s
    }
    ```

## Acquiring multiple locks

If you need to acquire multiple locks at once, use:

```php
$lock = $lockFactory->createMultiLock(['my_lock_name_1', 'my_lock_name_2']);
```

The object returned by `createMultiLock()` implements the same `LockInterface` as the single lock, so you can use it in exactly the same way.

The locks are acquired atomically, i.e. either all locks are acquired, or none of them are.

## Reentrancy

Locks in this library are **reentrant** (also known as recursive locks), meaning the same process can acquire a lock multiple times without causing a deadlock. This is particularly useful for recursive methods that call themselves, or when methods call other methods that also need the same lock.

**How It Works**

- Each time a process acquires a reentrant lock, an internal counter is incremented
- The lock is only fully released when the counter returns to zero
- Other processes must wait until the lock is completely released before they can acquire it

```php
$lock->acquire(); // Counter: 1 - blocks until the lock is available
$lock->acquire(); // Counter: 2 - returns immediately
$lock->acquire(); // Counter: 3 - returns immediately

$lock->release(); // Counter: 2 - lock is still held
$lock->release(); // Counter: 1 - lock is still held  
$lock->release(); // Counter: 0 - lock is now fully released
```

## Exceptions

Depending on the operation called, the following exceptions may be thrown:

- `LockAcquireException`
- `LockReleaseException`
- `LockWaitException`

All of these exceptions extend `LockException`, which can be used to catch all lock-related exceptions.

These exceptions **are only thrown when an error occurs**, not in normal conditions like failure to acquire a lock due
to another process holding it. For example, `tryAcquire()` will return `false` if the lock cannot be acquired
immediately, and only throw a `LockAcquireException` if an error occurs and the status of the lock cannot be determined.

> [!TIP]
> Check the source code of `LockInterface` for detailed information about the exceptions thrown by each method.

## Use in a Symfony project

In a Symfony project, add the following config, typically in `config/services.yaml`:

```yaml
services:
    Brick\Lock\LockFactoryInterface:
        class: Brick\Lock\LockFactory

    # Choose the driver that corresponds to your database:
    Brick\Lock\LockDriverInterface:
        class: Brick\Lock\Driver\MysqlLockDriver
        # class: Brick\Lock\Driver\MariadbLockDriver
        # class: Brick\Lock\Driver\PostgresLockDriver

    # Choose the connection you want to use:
    Brick\Lock\Database\ConnectionInterface:
        class: Brick\Lock\Database\Connection\DoctrineConnection
        # class: Brick\Lock\Database\Connection\PdoConnection
```

> [!TIP]
> In a typical Symfony project using the Doctrine ORM, you'll probably want to use `DoctrineConnection`.

You can now type-hint the `Brick\Lock\LockFactoryInterface` service in your code and use it to create locks.

## Alternatives

You may also want to consider the following projects:

- **[symfony/lock](https://symfony.com/doc/current/components/lock.html)**
- **[php-lock/lock](https://github.com/php-lock/lock)**

Please see the [alternatives](docs/alternatives.md) documentation for a detailed comparison of these libraries with brick/lock.
