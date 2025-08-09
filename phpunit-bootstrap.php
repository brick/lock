<?php

require 'vendor/autoload.php';

use Brick\Lock\Tests\Util\LockDriverFactory;
use Brick\Lock\Tests\Util\LockDriverFactoryFailure;

// Even though lock drivers are used in the worker script only, we try to instantiate the driver here first:
//   - if the driver cannot be created, we can display error messages early, before the tests start;
//   - if the driver can be created, we can display informational messages, e.g., the database version.
$result = LockDriverFactory::getDriver();

if ($result instanceof LockDriverFactoryFailure) {
    foreach ($result->errorMessages as $message) {
        echo "$message\n";
    }

    exit(1);
}

foreach ($result->infoMessages as $message) {
    echo "$message\n";
}
