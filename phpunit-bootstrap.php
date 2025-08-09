<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use Brick\Lock\Tests\Util\LockDriverFactory;
use Brick\Lock\Tests\Util\LockDriverFactoryException;

// Even though lock drivers are used in the worker script only, we try to instantiate the driver here first:
//   - if the driver cannot be created, we can display error messages early, before the tests start;
//   - if the driver can be created, we can display informational messages, e.g., the database version.

try {
    $lockDriverWithInfo = LockDriverFactory::getDriver();
} catch (LockDriverFactoryException $e) {
    foreach ($e->errorMessages as $errorMessage) {
        echo $errorMessage, PHP_EOL;
    }

    exit(1);
}

foreach ($lockDriverWithInfo->infoMessages as $infoMessage) {
    echo $infoMessage, PHP_EOL;
}
