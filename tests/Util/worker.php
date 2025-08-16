<?php

/**
 * This script is executed in a separate process.
 * It receives commands from the test suite via STDIN and executes them.
 */

declare(strict_types=1);

use Brick\Lock\Exception\LockException;
use Brick\Lock\Tests\Util\CommandInterface;
use Brick\Lock\Tests\Util\LockContext;
use Brick\Lock\Tests\Util\LockDriverFactory;
use Brick\Lock\Tests\Util\LockDriverFactoryException;
use function Opis\Closure\unserialize;

require __DIR__ . '/../../vendor/autoload.php';

try {
    $lockDriverWithInfo = LockDriverFactory::getDriver();
} catch (LockDriverFactoryException $e) {
    foreach ($e->errorMessages as $errorMessage) {
        fwrite(STDERR, "$errorMessage\n");
    }
    exit(1);
}

$helper = new LockContext(
    $lockDriverWithInfo->lockDriver,
    $lockDriverWithInfo->connection,
);

while (($line = fgets(STDIN)) !== false) {
    $serializedCommand = json_decode($line, associative: true, flags: JSON_THROW_ON_ERROR);

    if (! is_string($serializedCommand)) {
        throw new LogicException('Expected string, got ' . get_debug_type($serializedCommand));
    }

    $command = unserialize($serializedCommand);

    if (! $command instanceof CommandInterface) {
        throw new LogicException('Expected CommandInterface, got ' . get_debug_type($command));
    }

    try {
        $command->execute($helper);
    } catch (LockException $e) {
        $shortName = (new \ReflectionClass($e))->getShortName();
        $helper->write($shortName);
    }
}

// The process is supposed to be killed by the parent process.
fwrite(STDERR, 'Unexpected end of input');
exit(1);
