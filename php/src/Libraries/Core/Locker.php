<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Libraries\Core;

use App\Libraries\Core\Exception\LockAcquisitionException;
use Throwable;
use Yiisoft\Mutex\MutexInterface;
use Yiisoft\Mutex\Pgsql\PgsqlMutexFactory;
use Yiisoft\Mutex\Synchronizer;

class Locker
{
    private PgsqlMutexFactory $mutexFactory;

    private Synchronizer $synchronizer;

    private ?MutexInterface $mutex;

    public function __construct()
    {
        $params = Yii::params();
        $connection = new \PDO($params['yiisoft/db-pgsql']['dsn'], $params['yiisoft/db-pgsql']['username'], $params['yiisoft/db-pgsql']['password']);
        $this->mutexFactory = new PgsqlMutexFactory($connection);
        $this->synchronizer = new Synchronizer($this->mutexFactory);
    }

    public function run($critical, callable $callback, int $timeout = 0) : bool
    {
        try {
            $this->synchronizer->execute($critical, $callback, $timeout);
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    /**
     * @throws LockAcquisitionException
     */
    public function runOrFail($critical, callable $callback, int $timeout = 0) : void
    {
        try {
            $this->synchronizer->execute($critical, $callback, $timeout);
        } catch (Throwable $e) {
            throw new LockAcquisitionException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function acquire($critical, int $timeout = 0): bool
    {
        $this->mutex = $this->mutexFactory->create($critical);
        if (! $this->mutex->acquire($timeout)) {
            return false;
        }

        return true;
    }

    /**
     * @throws LockAcquisitionException
     */
    public function acquireOrFail($critical, int $timeout = 0): void
    {
        try {
            $this->mutex = $this->mutexFactory->createAndAcquire($critical, $timeout);
        } catch (Throwable $e) {
            throw new LockAcquisitionException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function release(): void
    {
        $this->mutex->release();
    }
}
