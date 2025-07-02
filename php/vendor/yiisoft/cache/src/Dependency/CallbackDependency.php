<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Dependency;

use Yiisoft\Cache\CacheInterface;

/**
 * CallbackDependency represents a dependency based on the result of a callback.
 *
 * The dependency is reported as unchanged if and only if the result of the callback is
 * the same as the one evaluated when storing the data to cache.
 */
final class CallbackDependency extends Dependency
{
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    protected function generateDependencyData(CacheInterface $cache): mixed
    {
        return ($this->callback)($cache);
    }
}
