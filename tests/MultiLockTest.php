<?php

declare(strict_types=1);

namespace Brick\Lock\Tests;

use Brick\Lock\Tests\Util\RemoteLock;
use PHPUnit\Framework\TestCase;

class MultiLockTest extends TestCase
{
    public function testAcquire(): void
    {
        $a = new RemoteLock();
        $b = new RemoteLock();
        $c = new RemoteLock();

        $a->acquireMulti(['id:1', 'id:2']);
        $a->expectWithin('1s', 'ACQUIRED');

        $b->acquireMulti(['id:3', 'id:4']);
        $b->expectWithin('1s', 'ACQUIRED');

        $c->acquireMulti(['id:2', 'id:3']);
        $c->expectNothingAfter('1s');

        $a->release();
        $a->expectWithin('1s', 'RELEASED');
        $c->expectNothingAfter('1s');

        $b->release();
        $b->expectWithin('1s', 'RELEASED');
        $c->expectWithin('1s', 'ACQUIRED');
    }

    public function testTryAcquire(): void
    {
        $a = new RemoteLock();
        $b = new RemoteLock();
        $c = new RemoteLock();

        $a->tryAcquireMulti(['id:3', 'id:4', 'id:5']);
        $a->expectWithin('1s', 'ACQUIRED');

        $b->tryAcquireMulti(['id:1', 'id:2', 'id:3']);
        $b->expectWithin('1s', 'NOT_ACQUIRED');

        // b should not hold id:1 and id:2
        $c->acquireMulti(['id:1', 'id:2']);
        $c->expectWithin('1s', 'ACQUIRED');
    }

    public function testTryAcquireWithTimeout(): void
    {
        $a = new RemoteLock();
        $b = new RemoteLock();
        $c = new RemoteLock();

        $a->tryAcquireMulti(['id:3', 'id:4', 'id:5']);
        $a->expectWithin('1s', 'ACQUIRED');

        $b->tryAcquireWithTimeoutMulti(['id:1', 'id:2', 'id:3'], 2);
        $b->expectNothingAfter('1s');
        $b->expectWithin('2s', 'NOT_ACQUIRED');

        $c->acquireMulti(['id:1', 'id:2', 'id:6']);
        $c->expectWithin('1s', 'ACQUIRED');

        $b->tryAcquireWithTimeoutMulti(['id:1', 'id:2', 'id:3'], 5);
        $b->expectNothingAfter('1s');

        $a->release();
        $a->expectWithin('1s', 'RELEASED');
        $b->expectNothingAfter('1s');

        $c->release();
        $c->expectWithin('1s', 'RELEASED');
        $b->expectWithin('2s', 'ACQUIRED');
    }

    public function testWait(): void
    {
        $a = new RemoteLock();
        $b = new RemoteLock();

        $a->acquireMulti(['id:3', 'id:4', 'id:5']);
        $a->expectWithin('1s', 'ACQUIRED');

        $b->waitMulti(['id:1', 'id:2', 'id:3']);
        $b->expectNothingAfter('1s');

        $a->release();
        $a->expectWithin('1s', 'RELEASED');
        $b->expectWithin('1s', 'WAIT_SUCCESS');
    }

    public function testTryWaitWithTimeout(): void
    {
        $a = new RemoteLock();
        $b = new RemoteLock();

        $a->acquireMulti(['id:3', 'id:4', 'id:5']);
        $a->expectWithin('1s', 'ACQUIRED');

        $b->tryWaitWithTimeoutMulti(['id:1', 'id:2', 'id:3'], 2);
        $b->expectNothingAfter('1s');
        $b->expectWithin('2s', 'WAIT_FAILURE');

        $b->tryWaitWithTimeoutMulti(['id:1', 'id:2', 'id:3'], 5);
        $b->expectNothingAfter('1s');

        $a->release();
        $a->expectWithin('1s', 'RELEASED');
        $b->expectWithin('2s', 'WAIT_SUCCESS');
    }
}
