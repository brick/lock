<?php

declare(strict_types=1);

namespace Brick\Lock\Tests\Util;

/**
 * Interface for worker commands.
 * Commands are created in tests, serialized, and sent to the worker.
 * The worker unserializes the command and executes it.
 */
interface CommandInterface
{
    public function execute(LockContext $context): void;
}
