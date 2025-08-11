<?php

declare(strict_types=1);

namespace Brick\Lock;

/**
 * @template T
 */
final readonly class SynchronizeSuccess
{
    /**
     * @param T $returnValue
     */
    public function __construct(
        public mixed $returnValue,
    ) {
    }
}
