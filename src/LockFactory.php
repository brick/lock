<?php

declare(strict_types=1);

namespace Brick\Lock;

use Override;

final readonly class LockFactory implements LockFactoryInterface
{
    public function __construct(
        private LockDriverInterface $driver,
    ) {
    }

    #[Override]
    public function createLock(string $name): LockInterface
    {
        return new Lock($this->driver, $name);
    }

    #[Override]
    public function createMultiLock(array $names): LockInterface
    {
        return new MultiLock($this->driver, $names);
    }
}
