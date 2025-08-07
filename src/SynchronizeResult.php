<?php

declare(strict_types=1);

namespace Brick\Lock;

/**
 * @template T
 */
final readonly class SynchronizeResult
{
    /**
     * @param T|null $returnValue
     */
    private function __construct(
        private bool $lockSuccess,
        private mixed $returnValue,
    ) {
    }

    /**
     * @param T $returnValue
     *
     * @return SynchronizeResult<T>
     */
    public static function lockSuccess(mixed $returnValue): SynchronizeResult
    {
        return new self(true, $returnValue);
    }

    /**
     * @return SynchronizeResult<T>
     */
    public static function lockFailure(): SynchronizeResult
    {
        /** @var SynchronizeResult<T> */
        return new self(false, null);
    }

    public function isLockSuccess(): bool
    {
        return $this->lockSuccess;
    }

    public function isLockFailure(): bool
    {
        return ! $this->lockSuccess;
    }

    /**
     * @return T
     *
     * @throws LockException If the lock was not acquired.
     */
    public function getReturnValue(): mixed
    {
        if (! $this->lockSuccess) {
            throw new LockException('Lock was not acquired, no return value available.');
        }

        /** @var T */
        return $this->returnValue;
    }
}
