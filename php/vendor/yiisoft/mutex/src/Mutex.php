<?php

declare(strict_types=1);

namespace Yiisoft\Mutex;

use Yiisoft\Mutex\Exception\MutexReleaseException;

use function md5;

/**
 * Provides basic functionality for creating drivers.
 *
 * @see MutexFactoryInterface
 */
abstract class Mutex implements MutexInterface
{
    use RetryAcquireTrait;

    private string $lockName;
    private string $mutexName;

    /**
     * @var array<string, true>
     */
    private static array $currentProcessLocks = [];

    public function __construct(string $driverName, string $mutexName)
    {
        $this->lockName = md5($driverName . $mutexName);
        $this->mutexName = $mutexName;
    }

    final public function __destruct()
    {
        $this->release();
    }

    final public function acquire(int $timeout = 0): bool
    {
        return $this->retryAcquire($timeout, function () use ($timeout): bool {
            if (!$this->isCurrentProcessLocked() && $this->acquireLock($timeout)) {
                return self::$currentProcessLocks[$this->lockName] = true;
            }

            return false;
        });
    }

    final public function release(): void
    {
        if (!$this->isCurrentProcessLocked()) {
            return;
        }

        if (!$this->releaseLock()) {
            throw new MutexReleaseException("Unable to release the \"$this->mutexName\" mutex.");
        }

        unset(self::$currentProcessLocks[$this->lockName]);
    }

    /**
     * Acquires lock.
     *
     * This method should be extended by a concrete Mutex implementations.
     *
     * @param int $timeout Time (in seconds) to wait for lock to be released. Defaults to zero meaning that method
     * will return false immediately in case lock was already acquired.
     *
     * @return bool The acquiring result.
     */
    abstract protected function acquireLock(int $timeout = 0): bool;

    /**
     * Releases lock.
     *
     * This method should be extended by a concrete Mutex implementations.
     *
     * @return bool The release result.
     */
    abstract protected function releaseLock(): bool;

    /**
     * Checks whether a lock has been set in the current process.
     *
     * @return bool Whether a lock has been set in the current process.
     */
    private function isCurrentProcessLocked(): bool
    {
        return isset(self::$currentProcessLocks[$this->lockName]);
    }
}
