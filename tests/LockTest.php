<?php

declare(strict_types=1);

namespace Brick\Lock\Tests;

class LockTest extends AbstractTestCase
{
    public function testAcquire(): void
    {
        $a = $this->newRemoteLock();
        $b = $this->newRemoteLock();

        $a->acquire('foo');
        $a->expectWithin('1s', 'ACQUIRED');

        $b->acquire('bar');
        $b->expectWithin('1s', 'ACQUIRED');

        $b->acquire('foo');
        $b->expectNothingAfter('1s');

        $a->release('foo');
        $a->expectWithin('1s', 'RELEASED');
        $b->expectWithin('1s', 'ACQUIRED');

        $a->acquire('bar');
        $b->expectNothingAfter('1s');

        $b->release('bar');
        $b->expectWithin('1s', 'RELEASED');
        $a->expectWithin('1s', 'ACQUIRED');
    }

    public function testTryAcquire(): void
    {
        $a = $this->newRemoteLock();
        $b = $this->newRemoteLock();

        $a->acquire('foo');
        $a->expectWithin('1s', 'ACQUIRED');

        $b->tryAcquire('foo');
        $b->expectWithin('1s', 'NOT_ACQUIRED');

        $a->release('foo');
        $a->expectWithin('1s', 'RELEASED');

        $b->tryAcquire('foo');
        $b->expectWithin('1s', 'ACQUIRED');
    }

    public function testTryAcquireWithTimeout(): void
    {
        $a = $this->newRemoteLock();
        $b = $this->newRemoteLock();

        $a->acquire('foo');
        $a->expectWithin('1s', 'ACQUIRED');

        $b->tryAcquireWithTimeout('foo', seconds: 2);
        $b->expectNothingAfter('1s');
        $b->expectWithin('2s', 'NOT_ACQUIRED');

        $a->release('foo');
        $a->expectWithin('1s', 'RELEASED');

        $b->tryAcquire('foo');
        $b->expectWithin('1s', 'ACQUIRED');
    }
}
