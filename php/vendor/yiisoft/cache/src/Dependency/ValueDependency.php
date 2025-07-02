<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Dependency;

use Yiisoft\Cache\CacheInterface;

/**
 * ValueDependency represents a dependency based on the specified value in the constructor.
 *
 * The dependency is reported as unchanged if and only if the specified value is
 * the same as the one evaluated when storing the data to cache.
 */
final class ValueDependency extends Dependency
{
    public function __construct(private mixed $value)
    {
    }

    protected function generateDependencyData(CacheInterface $cache): mixed
    {
        return $this->value;
    }
}
