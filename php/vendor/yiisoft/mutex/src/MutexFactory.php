<?php

declare(strict_types=1);

namespace Yiisoft\Mutex;

use Yiisoft\Mutex\Exception\MutexLockedException;

/**
 * Creates a mutex instance.
 *
 * @see MutexFactoryInterface
 */
abstract class MutexFactory implements MutexFactoryInterface
{
    final public function createAndAcquire(string $name, int $timeout = 0): MutexInterface
    {
        $mutex = $this->create($name);

        if (!$mutex->acquire($timeout)) {
            throw new MutexLockedException("Unable to acquire the \"$name\" mutex.");
        }

        return $mutex;
    }
}
