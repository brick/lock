<?php

declare(strict_types=1);

namespace Brick\Lock\Tests;

use Brick\Lock\Tests\Util\RemoteLock;
use PHPUnit\Framework\TestCase;

class LockTest extends TestCase
{
    public function testAcquire(): void
    {
        $a = new RemoteLock();
        $b = new RemoteLock();

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
        $a = new RemoteLock();
        $b = new RemoteLock();

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
        $a = new RemoteLock();
        $b = new RemoteLock();

        $a->acquire('foo');
        $a->expectWithin('1s', 'ACQUIRED');

        $b->tryAcquireWithTimeout('foo', timeoutSeconds: 2);
        $b->expectNothingAfter('1s');
        $b->expectWithin('2s', 'NOT_ACQUIRED');

        $a->release('foo');
        $a->expectWithin('1s', 'RELEASED');

        $b->tryAcquire('foo');
        $b->expectWithin('1s', 'ACQUIRED');
    }

    public function testReleaseWithoutAcquire(): void
    {
        $a = new RemoteLock();

        $a->release('foo');
        $a->expectWithin('1s', 'LockReleaseException');
    }

    public function testReleaseLockAcquiredByAnotherProcess(): void
    {
        $a = new RemoteLock();
        $b = new RemoteLock();

        $a->acquire('foo');
        $a->expectWithin('1s', 'ACQUIRED');

        $b->release('foo');
        $b->expectWithin('1s', 'LockReleaseException');
    }

    public function testWait(): void
    {
        $a = new RemoteLock();
        $b = new RemoteLock();

        $a->acquire('foo');
        $a->expectWithin('1s', 'ACQUIRED');

        $b->wait('foo');
        $b->expectNothingAfter('1s');

        $a->release('foo');
        $a->expectWithin('1s', 'RELEASED');
        $b->expectWithin('1s', 'WAIT_SUCCESS');

        // b should not hold the lock after wait()
        $a->acquire('foo');
        $a->expectWithin('1s', 'ACQUIRED');
    }

    public function testTryWaitWithTimeout(): void
    {
        $a = new RemoteLock();
        $b = new RemoteLock();

        $a->acquire('foo');
        $a->expectWithin('1s', 'ACQUIRED');

        $b->tryWaitWithTimeout('foo', timeoutSeconds: 2);
        $b->expectNothingAfter('1s');
        $b->expectWithin('2s', 'WAIT_FAILURE');

        $a->release('foo');
        $a->expectWithin('1s', 'RELEASED');

        $b->tryWaitWithTimeout('foo', timeoutSeconds: 2);
        $b->expectWithin('1s', 'WAIT_SUCCESS');
    }

    public function testSynchronizeReturningTask(): void
    {
        $a = new RemoteLock();
        $b = new RemoteLock();

        $a->synchronizeReturningTask('foo', taskDurationSeconds: 3, taskReturnValue: 'FirstTaskSuccess');
        $a->expectNothingAfter('1s');

        $b->synchronizeReturningTask('foo', taskDurationSeconds: 0, taskReturnValue: 'SecondTaskSuccess');
        $b->expectNothingAfter('1s');

        $a->expectWithin('2s', 'SYNC_LOCK_SUCCESS;RETURN:FirstTaskSuccess');
        $b->expectWithin('1s', 'SYNC_LOCK_SUCCESS;RETURN:SecondTaskSuccess');
    }

    public function testSynchronizeThrowingTask(): void
    {
        $a = new RemoteLock();
        $b = new RemoteLock();

        $a->synchronizeThrowingTask('foo', taskDurationSeconds: 3, taskExceptionMessage: 'FirstTaskError');
        $a->expectNothingAfter('1s');

        $b->synchronizeReturningTask('foo', taskDurationSeconds: 0, taskReturnValue: 'SecondTaskSuccess');
        $b->expectNothingAfter('1s');

        $a->expectWithin('2s', 'SYNC_LOCK_SUCCESS;EXCEPTION:FirstTaskError');
        $b->expectWithin('1s', 'SYNC_LOCK_SUCCESS;RETURN:SecondTaskSuccess');
    }

    public function testTrySynchronizeReturningTask(): void
    {
        $a = new RemoteLock();
        $b = new RemoteLock();

        $a->trySynchronizeReturningTask('foo', taskDurationSeconds: 2, taskReturnValue: 'FirstTaskSuccess');
        $a->expectNothingAfter('1s');

        $b->trySynchronizeReturningTask('foo', taskDurationSeconds: 0, taskReturnValue: 'SecondTaskSuccess');
        $b->expectWithin('1s', 'SYNC_LOCK_FAILURE');

        $a->expectWithin('2s', 'SYNC_LOCK_SUCCESS;RETURN:FirstTaskSuccess');

        $b->trySynchronizeReturningTask('foo', taskDurationSeconds: 0, taskReturnValue: 'SecondTaskSuccess');
        $b->expectWithin('1s', 'SYNC_LOCK_SUCCESS;RETURN:SecondTaskSuccess');
    }

    public function testTrySynchronizeThrowingTask(): void
    {
        $a = new RemoteLock();
        $b = new RemoteLock();

        $a->trySynchronizeThrowingTask('foo', taskDurationSeconds: 2, taskExceptionMessage: 'FirstTaskError');
        $a->expectNothingAfter('1s');

        $b->trySynchronizeReturningTask('foo', taskDurationSeconds: 0, taskReturnValue: 'SecondTaskSuccess');
        $b->expectWithin('1s', 'SYNC_LOCK_FAILURE');

        $a->expectWithin('2s', 'SYNC_LOCK_SUCCESS;EXCEPTION:FirstTaskError');

        $b->trySynchronizeReturningTask('foo', taskDurationSeconds: 0, taskReturnValue: 'SecondTaskSuccess');
        $b->expectWithin('1s', 'SYNC_LOCK_SUCCESS;RETURN:SecondTaskSuccess');
    }

    public function testTrySynchronizeWithTimeoutReturningTask(): void
    {
        $a = new RemoteLock();
        $b = new RemoteLock();

        $a->trySynchronizeWithTimeoutReturningTask('foo', timeoutSeconds: 1, taskDurationSeconds: 4, taskReturnValue: 'FirstTaskSuccess');
        $a->expectNothingAfter('1s');

        $b->trySynchronizeWithTimeoutReturningTask('foo', timeoutSeconds: 2, taskDurationSeconds: 0, taskReturnValue: 'SecondTaskSuccess');
        $b->expectNothingAfter('1s');
        $b->expectWithin('2s', 'SYNC_LOCK_FAILURE');

        $a->expectWithin('2s', 'SYNC_LOCK_SUCCESS;RETURN:FirstTaskSuccess');

        $b->trySynchronizeWithTimeoutReturningTask('foo', timeoutSeconds: 1, taskDurationSeconds: 0, taskReturnValue: 'SecondTaskSuccess');
        $b->expectWithin('1s', 'SYNC_LOCK_SUCCESS;RETURN:SecondTaskSuccess');
    }

    public function testTrySynchronizeWithTimeoutThrowingTask(): void
    {
        $a = new RemoteLock();
        $b = new RemoteLock();

        $a->trySynchronizeWithTimeoutThrowingTask('foo', timeoutSeconds: 1, taskDurationSeconds: 4, taskExceptionMessage: 'FirstTaskError');
        $a->expectNothingAfter('1s');

        $b->trySynchronizeWithTimeoutReturningTask('foo', timeoutSeconds: 2, taskDurationSeconds: 0, taskReturnValue: 'SecondTaskSuccess');
        $b->expectNothingAfter('1s');
        $b->expectWithin('2s', 'SYNC_LOCK_FAILURE');

        $a->expectWithin('2s', 'SYNC_LOCK_SUCCESS;EXCEPTION:FirstTaskError');

        $b->trySynchronizeWithTimeoutReturningTask('foo', timeoutSeconds: 1, taskDurationSeconds: 0, taskReturnValue: 'SecondTaskSuccess');
        $b->expectWithin('1s', 'SYNC_LOCK_SUCCESS;RETURN:SecondTaskSuccess');
    }
}
