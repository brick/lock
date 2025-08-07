## Brick\Lock

<img src="https://raw.githubusercontent.com/brick/brick/master/logo.png" alt="" align="left" height="64">

A PHP library to work with advisory locks.

[![Build Status](https://github.com/brick/lock/workflows/CI/badge.svg)](https://github.com/brick/lock/actions)
[![Coverage Status](https://coveralls.io/repos/github/brick/lock/badge.svg?branch=master)](https://coveralls.io/github/brick/lock?branch=master)
[![Latest Stable Version](https://poser.pugx.org/brick/lock/v/stable)](https://packagist.org/packages/brick/lock)
[![Total Downloads](https://poser.pugx.org/brick/lock/downloads)](https://packagist.org/packages/brick/lock)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](http://opensource.org/licenses/MIT)

### Installation

This library is installable via [Composer](https://getcomposer.org/):

```bash
composer require brick/lock
```

### Requirements

This library requires PHP 8.2 or later.

### Overview

To be written.

### Use in a Symfony project

In a Symfony project using the Doctrine ORM, add the following config, typically in `config/services.yaml`:

```yaml
services:
    Brick\Lock\LockFactoryInterface:
        class: Brick\Lock\LockFactory

    Brick\Lock\LockDriverInterface:
        # choose the driver that corresponds to your database:
        class: Brick\Lock\Driver\MysqlLockDriver
        # class: Brick\Lock\Driver\PostgresLockDriver

    Brick\Lock\Database\ConnectionInterface:
        # choose the connection you want to use; in a typical Symfony project
        # with the Doctrine ORM, you'll probably want to use DoctrineConnection:
        class: Brick\Lock\Database\Connection\DoctrineConnection
        # class: Brick\Lock\Database\Connection\PdoConnection
```

You can now type-hint the `Brick\Lock\LockFactoryInterface` service in your code and use it to create locks.
