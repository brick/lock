<?php

declare(strict_types=1);

namespace Brick\Lock;

final readonly class LockFactory implements LockFactoryInterface
{
    public function __construct(
        private LockDriverInterface $driver,
    ) {
    }

    public function createLock(string $name): LockInterface
    {
        return new Lock($this->driver, $name);
    }

    public function createMultiLock(array $names): LockInterface
    {
        return new MultiLock($this->driver, $names);
    }
}
