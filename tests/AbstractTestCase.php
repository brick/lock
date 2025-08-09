<?php

declare(strict_types=1);

namespace Brick\Lock\Tests;

use Brick\Lock\Tests\Util\RemoteLock;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    public function newRemoteLock(): RemoteLock
    {
        return new RemoteLock();
    }
}
