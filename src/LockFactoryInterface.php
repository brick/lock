<?php

declare(strict_types=1);

namespace Brick\Lock;

use InvalidArgumentException;

/**
 * Creates lock objects that can acquire advisory locks.
 *
 * These locks allow multiple processes to coordinate access to shared resources.
 */
interface LockFactoryInterface
{
    /**
     * Creates a named lock without acquiring it.
     *
     * @param string $name The lock name. This arbitrary string should uniquely identify the resource being locked.
     */
    public function createLock(string $name): LockInterface;

    /**
     * Creates an atomic lock for multiple resources.
     *
     * Acquiring a multi lock is an atomic operation: either all locks are acquired, or none are.
     *
     * @param string[] $names The lock names. Each name should uniquely identify the resource being locked.
     *
     * @throws InvalidArgumentException If the number of locks is less than 2.
     */
    public function createMultiLock(array $names): LockInterface;
}
