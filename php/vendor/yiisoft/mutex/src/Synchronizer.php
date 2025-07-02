<?php

declare(strict_types=1);

namespace Yiisoft\Mutex;

use ErrorException;
use Throwable;
use Yiisoft\Mutex\Exception\MutexLockedException;

use function error_reporting;
use function restore_error_handler;
use function set_error_handler;

/**
 * Executes a callback in synchronized mode, i.e. only a single instance of the callback is executed at the same time:
 *
 * ```php
 * $synchronizer = new \Yiisoft\Mutex\Synchronizer(new MyMutexFactory());
 *
 * $newCount = $synchronizer->execute('critical_logic', function () {
 *     return $counter->increase();
 * }, 10);
 * ```
 */
final class Synchronizer
{
    private MutexFactoryInterface $mutexFactory;

    public function __construct(MutexFactoryInterface $mutexFactory)
    {
        $this->mutexFactory = $mutexFactory;
    }

    /**
     * Executes a PHP callable with a lock and returns the result.
     *
     * @param string $name Name of the mutex to acquire.
     * @param callable $callback PHP callable to execution.
     * @param int $timeout Time (in seconds) to wait for lock to be released. Defaults to zero meaning that
     * method {@see MutexInterface::acquire()} will return false immediately in case lock was already acquired.
     *
     * @throws MutexLockedException If unable to acquire lock.
     * @throws Throwable If an error occurred during the execution of the PHP callable.
     *
     * @return mixed The result of the PHP callable execution.
     */
    public function execute(string $name, callable $callback, int $timeout = 0)
    {
        $mutex = $this->mutexFactory->createAndAcquire($name, $timeout);

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                // This error code is not included in error_reporting.
                return true;
            }

            throw new ErrorException($message, $severity, $severity, $file, $line);
        });

        try {
            /** @var mixed $result */
            return $callback();
        } finally {
            restore_error_handler();
            $mutex->release();
        }
    }
}
