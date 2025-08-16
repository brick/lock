<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util;

use Brick\Lock\Exception\LockException;
use LogicException;

use function Opis\Closure\unserialize;

/**
 * Executed in a separate process; receives lock commands from the test suite via STDIN and executes them.
 */
class Worker
{
    public function run(): never
    {
        try {
            $lockDriverWithInfo = LockDriverFactory::getDriver();
        } catch (LockDriverFactoryException $e) {
            foreach ($e->errorMessages as $errorMessage) {
                $this->writeErr($errorMessage);
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
        $this->writeErr('Unexpected end of input');
        exit(1);
    }

    private function writeErr(string $message): void
    {
        fwrite(STDERR, "$message\n");
    }
}
