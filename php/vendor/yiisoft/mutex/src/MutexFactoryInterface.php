<?php

declare(strict_types=1);

namespace Yiisoft\Mutex;

use Yiisoft\Mutex\Exception\MutexLockedException;

/**
 * Allows you to create a mutex instance.
 */
interface MutexFactoryInterface
{
    /**
     * Creates a mutex.
     *
     * @param string $name Name of the mutex to create.
     */
    public function create(string $name): MutexInterface;

    /**
     * Creates a mutex and acquires a lock.
     *
     * @param string $name Name of the mutex to create.
     * @param int $timeout Time (in seconds) to wait for lock to be released. Defaults to zero meaning that
     * method {@see MutexInterface::acquire()} will return false immediately in case lock was already acquired.
     *
     * @throws MutexLockedException If unable to acquire lock.
     *
     * @return MutexInterface
     */
    public function createAndAcquire(string $name, int $timeout = 0): MutexInterface;
}
