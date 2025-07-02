<?php

declare(strict_types=1);

namespace Yiisoft\Mutex;

/**
 * Simplest way to use mutex:
 *
 * ```php
 * $mutex = new \Yiisoft\Mutex\SimpleMutex(new MyMutexFactory());
 *
 * if (!$mutex->acquire('critical_logic', 1000)) {
 *     throw new \Yiisoft\Mutex\Exception\MutexLockedException('Unable to acquire the "critical_logic" mutex.');
 * }
 *
 * // ...
 * // business logic execution
 * // ...
 *
 * $mutex->release();
 * ```
 */
final class SimpleMutex
{
    private MutexFactoryInterface $mutexFactory;

    /**
     * @var MutexInterface[]
     */
    private array $acquired = [];

    public function __construct(MutexFactoryInterface $mutexFactory)
    {
        $this->mutexFactory = $mutexFactory;
    }

    /**
     * Acquires a lock with a given name.
     *
     * @param string $name Name of the mutex to acquire.
     * @param int $timeout Time (in seconds) to wait for lock to be released. Defaults to zero meaning that method
     * will return false immediately in case lock was already acquired.
     *
     * @return bool Whether a lock is acquired.
     */
    public function acquire(string $name, int $timeout = 0): bool
    {
        $mutex = $this->mutexFactory->create($name);

        if ($mutex->acquire($timeout)) {
            $this->acquired[$name] = $mutex;
            return true;
        }

        return false;
    }

    /**
     * Releases a lock with a given name.
     */
    public function release(string $name): void
    {
        if (!isset($this->acquired[$name])) {
            return;
        }

        $this->acquired[$name]->release();
        unset($this->acquired[$name]);
    }
}
