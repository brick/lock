<?php

declare(strict_types=1);

namespace Brick\Lock\Tests;

use Brick\Lock\Tests\Util\RemoteWorker;
use PHPUnit\Framework\TestCase;

class LockTest extends TestCase
{
    public function testAcquire(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $aFoo = $a->createLock('foo');
        $aBar = $a->createLock('bar');
        $bFoo = $b->createLock('foo');
        $bBar = $b->createLock('bar');

        $aFoo->acquire();
        $a->expectWithin('1s', 'ACQUIRED');

        $bBar->acquire();
        $b->expectWithin('1s', 'ACQUIRED');

        $bFoo->acquire();
        $b->expectNothingAfter('1s');

        $aFoo->release();
        $a->expectWithin('1s', 'RELEASED');
        $b->expectWithin('1s', 'ACQUIRED');

        $aBar->acquire();
        $a->expectNothingAfter('1s');

        $bBar->release();
        $b->expectWithin('1s', 'RELEASED');
        $a->expectWithin('1s', 'ACQUIRED');
    }

    public function testTryAcquire(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $aFoo = $a->createLock('foo');
        $bFoo = $b->createLock('foo');

        $aFoo->acquire();
        $a->expectWithin('1s', 'ACQUIRED');

        $bFoo->tryAcquire();
        $b->expectWithin('1s', 'NOT_ACQUIRED');

        $aFoo->release();
        $a->expectWithin('1s', 'RELEASED');

        $bFoo->tryAcquire();
        $b->expectWithin('1s', 'ACQUIRED');
    }

    public function testTryAcquireWithTimeout(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $aFoo = $a->createLock('foo');
        $bFoo = $b->createLock('foo');

        $aFoo->acquire();
        $a->expectWithin('1s', 'ACQUIRED');

        $bFoo->tryAcquireWithTimeout(seconds: 2);
        $b->expectNothingAfter('1s');
        $b->expectWithin('2s', 'NOT_ACQUIRED');

        $aFoo->release();
        $a->expectWithin('1s', 'RELEASED');

        $bFoo->tryAcquire();
        $b->expectWithin('1s', 'ACQUIRED');
    }

    public function testReleaseWithoutAcquire(): void
    {
        $a = new RemoteWorker();

        $aFoo = $a->createLock('foo');

        $aFoo->release();
        $a->expectWithin('1s', 'LockReleaseException');
    }

    public function testReleaseLockAcquiredByAnotherProcess(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $aFoo = $a->createLock('foo');
        $bFoo = $b->createLock('foo');

        $aFoo->acquire();
        $a->expectWithin('1s', 'ACQUIRED');

        $bFoo->release();
        $b->expectWithin('1s', 'LockReleaseException');
    }

    public function testWait(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $aFoo = $a->createLock('foo');
        $bFoo = $b->createLock('foo');

        $aFoo->acquire();
        $a->expectWithin('1s', 'ACQUIRED');

        $bFoo->wait();
        $b->expectNothingAfter('1s');

        $aFoo->release();
        $a->expectWithin('1s', 'RELEASED');
        $b->expectWithin('1s', 'WAIT_SUCCESS');

        // b should not hold the lock after wait()
        $aFoo->acquire();
        $a->expectWithin('1s', 'ACQUIRED');
    }

    public function testTryWaitWithTimeout(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $aFoo = $a->createLock('foo');
        $bFoo = $b->createLock('foo');

        $aFoo->acquire();
        $a->expectWithin('1s', 'ACQUIRED');

        $bFoo->tryWaitWithTimeout(seconds: 2);
        $b->expectNothingAfter('1s');
        $b->expectWithin('2s', 'WAIT_FAILURE');

        $aFoo->release();
        $a->expectWithin('1s', 'RELEASED');

        $bFoo->tryWaitWithTimeout(seconds: 2);
        $b->expectWithin('1s', 'WAIT_SUCCESS');
    }

    public function testSynchronizeReturningTask(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $aFoo = $a->createLock('foo');
        $bFoo = $b->createLock('foo');

        $aFoo->synchronizeReturn(taskDurationSeconds: 3, taskReturnValue: 'FirstTaskSuccess');
        $a->expectNothingAfter('1s');

        $bFoo->synchronizeReturn(taskDurationSeconds: 0, taskReturnValue: 'SecondTaskSuccess');
        $b->expectNothingAfter('1s');

        $a->expectWithin('2s', 'SYNC_LOCK_SUCCESS;RETURN:FirstTaskSuccess');
        $b->expectWithin('1s', 'SYNC_LOCK_SUCCESS;RETURN:SecondTaskSuccess');
    }

    public function testSynchronizeThrowingTask(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $aFoo = $a->createLock('foo');
        $bFoo = $b->createLock('foo');

        $aFoo->synchronizeThrow(taskDurationSeconds: 3, taskExceptionMessage: 'FirstTaskError');
        $a->expectNothingAfter('1s');

        $bFoo->synchronizeReturn(taskDurationSeconds: 0, taskReturnValue: 'SecondTaskSuccess');
        $b->expectNothingAfter('1s');

        $a->expectWithin('2s', 'SYNC_LOCK_SUCCESS;EXCEPTION:FirstTaskError');
        $b->expectWithin('1s', 'SYNC_LOCK_SUCCESS;RETURN:SecondTaskSuccess');
    }

    public function testTrySynchronizeReturningTask(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $aFoo = $a->createLock('foo');
        $bFoo = $b->createLock('foo');

        $aFoo->trySynchronizeReturn(taskDurationSeconds: 2, taskReturnValue: 'FirstTaskSuccess');
        $a->expectNothingAfter('1s');

        $bFoo->trySynchronizeReturn(taskDurationSeconds: 0, taskReturnValue: 'SecondTaskSuccess');
        $b->expectWithin('1s', 'SYNC_LOCK_FAILURE');

        $a->expectWithin('2s', 'SYNC_LOCK_SUCCESS;RETURN:FirstTaskSuccess');

        $bFoo->trySynchronizeReturn(taskDurationSeconds: 0, taskReturnValue: 'SecondTaskSuccess');
        $b->expectWithin('1s', 'SYNC_LOCK_SUCCESS;RETURN:SecondTaskSuccess');
    }

    public function testTrySynchronizeThrowingTask(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $aFoo = $a->createLock('foo');
        $bFoo = $b->createLock('foo');

        $aFoo->trySynchronizeThrow(taskDurationSeconds: 2, taskExceptionMessage: 'FirstTaskError');
        $a->expectNothingAfter('1s');

        $bFoo->trySynchronizeReturn(taskDurationSeconds: 0, taskReturnValue: 'SecondTaskSuccess');
        $b->expectWithin('1s', 'SYNC_LOCK_FAILURE');

        $a->expectWithin('2s', 'SYNC_LOCK_SUCCESS;EXCEPTION:FirstTaskError');

        $bFoo->trySynchronizeReturn(taskDurationSeconds: 0, taskReturnValue: 'SecondTaskSuccess');
        $b->expectWithin('1s', 'SYNC_LOCK_SUCCESS;RETURN:SecondTaskSuccess');
    }

    public function testTrySynchronizeWithTimeoutReturningTask(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $aFoo = $a->createLock('foo');
        $bFoo = $b->createLock('foo');

        $aFoo->trySynchronizeWithTimeoutReturn(timeoutSeconds: 1, taskDurationSeconds: 4, taskReturnValue: 'FirstTaskSuccess');
        $a->expectNothingAfter('1s');

        $bFoo->trySynchronizeWithTimeoutReturn(timeoutSeconds: 2, taskDurationSeconds: 0, taskReturnValue: 'SecondTaskSuccess');
        $b->expectNothingAfter('1s');
        $b->expectWithin('2s', 'SYNC_LOCK_FAILURE');

        $a->expectWithin('2s', 'SYNC_LOCK_SUCCESS;RETURN:FirstTaskSuccess');

        $bFoo->trySynchronizeWithTimeoutReturn(timeoutSeconds: 1, taskDurationSeconds: 0, taskReturnValue: 'SecondTaskSuccess');
        $b->expectWithin('1s', 'SYNC_LOCK_SUCCESS;RETURN:SecondTaskSuccess');
    }

    public function testTrySynchronizeWithTimeoutThrowingTask(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $aFoo = $a->createLock('foo');
        $bFoo = $b->createLock('foo');

        $aFoo->trySynchronizeWithTimeoutThrow(timeoutSeconds: 1, taskDurationSeconds: 4, taskExceptionMessage: 'FirstTaskError');
        $a->expectNothingAfter('1s');

        $bFoo->trySynchronizeWithTimeoutReturn(timeoutSeconds: 2, taskDurationSeconds: 0, taskReturnValue: 'SecondTaskSuccess');
        $b->expectNothingAfter('1s');
        $b->expectWithin('2s', 'SYNC_LOCK_FAILURE');

        $a->expectWithin('2s', 'SYNC_LOCK_SUCCESS;EXCEPTION:FirstTaskError');

        $bFoo->trySynchronizeWithTimeoutReturn(timeoutSeconds: 1, taskDurationSeconds: 0, taskReturnValue: 'SecondTaskSuccess');
        $b->expectWithin('1s', 'SYNC_LOCK_SUCCESS;RETURN:SecondTaskSuccess');
    }

    public function testLockIsReentrant(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $aFoo = $a->createLock('foo');
        $bFoo = $b->createLock('foo');

        $aFoo->acquire();
        $a->expectWithin('1s', 'ACQUIRED');

        $bFoo->acquire();
        $b->expectNothingAfter('1s');

        $aFoo->acquire();
        $a->expectWithin('1s', 'ACQUIRED');

        $aFoo->release();
        $a->expectWithin('1s', 'RELEASED');
        $b->expectNothingAfter('1s');

        $aFoo->release();
        $a->expectWithin('1s', 'RELEASED');
        $b->expectWithin('1s', 'ACQUIRED');
    }

    public function testDeadlock(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $aFoo = $a->createLock('foo');
        $aBar = $a->createLock('bar');
        $bFoo = $b->createLock('foo');
        $bBar = $b->createLock('bar');

        $aFoo->acquire();
        $a->expectWithin('1s', 'ACQUIRED');

        $bBar->acquire();
        $b->expectWithin('1s', 'ACQUIRED');

        $aBar->acquire();
        $a->expectNothingAfter('1s');

        $bFoo->acquire();
        $b->expectWithin('3s', 'LockAcquireException'); // postgres takes > 1s to detect deadlock
    }

    public function testLockIsReleasedWhenConnectionIsClosed(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $aFoo = $a->createLock('foo');
        $bFoo = $b->createLock('foo');

        $aFoo->acquire();
        $a->expectWithin('1s', 'ACQUIRED');

        $bFoo->acquire();
        $b->expectNothingAfter('1s');

        $a->kill();
        $b->expectWithin('1s', 'ACQUIRED');
    }

    public function testStartingAndCommittingTransactionAfterLock(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $aFoo = $a->createLock('foo');
        $bFoo = $b->createLock('foo');

        $aFoo->acquire();
        $a->expectWithin('1s', 'ACQUIRED');

        $bFoo->acquire();
        $b->expectNothingAfter('1s');

        $a->beginTransaction();
        $a->expectWithin('1s', 'BEGIN');
        $b->expectNothingAfter('1s');

        $a->commit();
        $a->expectWithin('1s', 'COMMIT');
        $b->expectNothingAfter('1s');

        $aFoo->release();
        $a->expectWithin('1s', 'RELEASED');
        $b->expectWithin('1s', 'ACQUIRED');
    }

    public function testStartingAndRollingBackTransactionAfterLock(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $aFoo = $a->createLock('foo');
        $bFoo = $b->createLock('foo');

        $aFoo->acquire();
        $a->expectWithin('1s', 'ACQUIRED');

        $bFoo->acquire();
        $b->expectNothingAfter('1s');

        $a->beginTransaction();
        $a->expectWithin('1s', 'BEGIN');
        $b->expectNothingAfter('1s');

        $a->rollBack();
        $a->expectWithin('1s', 'ROLLBACK');
        $b->expectNothingAfter('1s');

        $aFoo->release();
        $a->expectWithin('1s', 'RELEASED');
        $b->expectWithin('1s', 'ACQUIRED');
    }

    public function testLockAcquiredDuringTransactionIsKeptAfterCommit(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $aFoo = $a->createLock('foo');
        $bFoo = $b->createLock('foo');

        $a->beginTransaction();
        $a->expectWithin('1s', 'BEGIN');

        $aFoo->acquire();
        $a->expectWithin('1s', 'ACQUIRED');

        $bFoo->acquire();
        $b->expectNothingAfter('1s');

        $a->commit();
        $a->expectWithin('1s', 'COMMIT');
        $b->expectNothingAfter('1s');

        $aFoo->release();
        $a->expectWithin('1s', 'RELEASED');
        $b->expectWithin('1s', 'ACQUIRED');
    }

    public function testLockAcquiredDuringTransactionIsKeptAfterRollback(): void
    {
        $a = new RemoteWorker();
        $b = new RemoteWorker();

        $aFoo = $a->createLock('foo');
        $bFoo = $b->createLock('foo');

        $a->beginTransaction();
        $a->expectWithin('1s', 'BEGIN');

        $aFoo->acquire();
        $a->expectWithin('1s', 'ACQUIRED');

        $bFoo->acquire();
        $b->expectNothingAfter('1s');

        $a->rollBack();
        $a->expectWithin('1s', 'ROLLBACK');
        $b->expectNothingAfter('1s');

        $aFoo->release();
        $a->expectWithin('1s', 'RELEASED');
        $b->expectWithin('1s', 'ACQUIRED');
    }
}
