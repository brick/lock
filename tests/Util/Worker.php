<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util;

use Brick\Lock\Exception\LockException;
use CuyZ\Valinor\MapperBuilder;

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

        $helper = new LockHelper(
            $lockDriverWithInfo->lockDriver,
            $lockDriverWithInfo->connection,
        );

        while (($line = fgets(STDIN)) !== false) {
            /** @var object{className: string, data: array<string, mixed>} $message */
            $message = json_decode($line, associative: true, flags: JSON_THROW_ON_ERROR);

            $mapper = (new MapperBuilder())->allowPermissiveTypes()->mapper();
            $message = $mapper->map(
                'array{
                    className: class-string<Brick\Lock\Tests\Util\CommandInterface>,
                    data: array<string, mixed>
                }',
                $message,
            );

            $mapper = (new MapperBuilder())->mapper();
            $command = $mapper->map(
                $message['className'],
                $message['data'],
            );

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
