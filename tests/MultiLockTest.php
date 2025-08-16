<?php

declare(strict_types=1);

namespace Brick\Lock\Tests;

use Brick\Lock\Tests\Util\RemoteWorker;
use PHPUnit\Framework\TestCase;

class MultiLockTest extends TestCase
{
    public function testAcquire(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();
        $c = new RemoteWorker();

        $a12 = $a->createMultiLock(['id:1', 'id:2']);
        $b34 = $b->createMultiLock(['id:3', 'id:4']);
        $c23 = $c->createMultiLock(['id:2', 'id:3']);

        $a12->acquire();
        $a->expectWithin('1s', 'ACQUIRED');

        $b34->acquire();
        $b->expectWithin('1s', 'ACQUIRED');

        $c23->acquire();
        $c->expectNothingAfter('1s');

        $a12->release();
        $a->expectWithin('1s', 'RELEASED');
        $c->expectNothingAfter('1s');

        $b34->release();
        $b->expectWithin('1s', 'RELEASED');
        $c->expectWithin('1s', 'ACQUIRED');
    }

    public function testTryAcquire(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();
        $c = new RemoteWorker();

        $a345 = $a->createMultiLock(['id:3', 'id:4', 'id:5']);
        $b123 = $b->createMultiLock(['id:1', 'id:2', 'id:3']);
        $c12 = $c->createMultiLock(['id:1', 'id:2']);

        $a345->tryAcquire();
        $a->expectWithin('1s', 'ACQUIRED');

        $b123->tryAcquire();
        $b->expectWithin('1s', 'NOT_ACQUIRED');

        // b should not hold id:1 and id:2
        $c12->acquire();
        $c->expectWithin('1s', 'ACQUIRED');
    }

    public function testTryAcquireWithTimeout(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();
        $c = new RemoteWorker();

        $a345 = $a->createMultiLock(['id:3', 'id:4', 'id:5']);
        $b123 = $b->createMultiLock(['id:1', 'id:2', 'id:3']);
        $c126 = $c->createMultiLock(['id:1', 'id:2', 'id:6']);

        $a345->tryAcquire();
        $a->expectWithin('1s', 'ACQUIRED');

        $b123->tryAcquireWithTimeout(seconds: 2);
        $b->expectNothingAfter('1s');
        $b->expectWithin('2s', 'NOT_ACQUIRED');

        $c126->acquire();
        $c->expectWithin('1s', 'ACQUIRED');

        $b123->tryAcquireWithTimeout(seconds: 5);
        $b->expectNothingAfter('1s');

        $a345->release();
        $a->expectWithin('1s', 'RELEASED');
        $b->expectNothingAfter('1s');

        $c126->release();
        $c->expectWithin('1s', 'RELEASED');
        $b->expectWithin('2s', 'ACQUIRED');
    }

    public function testWait(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $a345 = $a->createMultiLock(['id:3', 'id:4', 'id:5']);
        $b123 = $b->createMultiLock(['id:1', 'id:2', 'id:3']);

        $a345->acquire();
        $a->expectWithin('1s', 'ACQUIRED');

        $b123->wait();
        $b->expectNothingAfter('1s');

        $a345->release();
        $a->expectWithin('1s', 'RELEASED');
        $b->expectWithin('1s', 'WAIT_SUCCESS');
    }

    public function testTryWaitWithTimeout(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $a345 = $a->createMultiLock(['id:3', 'id:4', 'id:5']);
        $b123 = $b->createMultiLock(['id:1', 'id:2', 'id:3']);

        $a345->acquire();
        $a->expectWithin('1s', 'ACQUIRED');

        $b123->tryWaitWithTimeout(seconds: 2);
        $b->expectNothingAfter('1s');
        $b->expectWithin('2s', 'WAIT_FAILURE');

        $b123->tryWaitWithTimeout(seconds: 5);
        $b->expectNothingAfter('1s');

        $a345->release();
        $a->expectWithin('1s', 'RELEASED');
        $b->expectWithin('2s', 'WAIT_SUCCESS');
    }
}
