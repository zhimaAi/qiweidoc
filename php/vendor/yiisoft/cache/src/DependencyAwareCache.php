<?php

declare(strict_types=1);

namespace Yiisoft\Cache;

use DateInterval;
use Psr\SimpleCache\CacheInterface as PsrSimpleCacheInterface;
use Yiisoft\Cache\Metadata\CacheItem;

use function is_array;

/**
 * Cache provides support for the data caching, including dependencies.
 * The actual data caching is performed via {@see DependencyAwareCache::handler()}.
 *
 * @internal
 */
final class DependencyAwareCache implements PsrSimpleCacheInterface
{
    /**
     * @param CacheInterface $cache The actual cache.
     * @param PsrSimpleCacheInterface $handler The actual cache handler.
     */
    public function __construct(
        private CacheInterface $cache,
        private PsrSimpleCacheInterface $handler
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        /** @var mixed */
        $value = $this->handler->get($key, $default);
        return $this->checkAndGetValue($key, $value, $default);
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        return $this->handler->set($key, $value, $ttl);
    }

    public function delete($key): bool
    {
        return $this->handler->delete($key);
    }

    public function clear(): bool
    {
        return $this->handler->clear();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];

        /** @var mixed $value */
        foreach ($this->handler->getMultiple($keys, $default) as $key => $value) {
            /** @var mixed */
            $values[$key] = $this->checkAndGetValue($key, $value, $default);
        }

        return $values;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        return $this->handler->setMultiple($values, $ttl);
    }

    public function deleteMultiple($keys): bool
    {
        return $this->handler->deleteMultiple($keys);
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Gets the raw cache value.
     *
     * @param string $key The unique key of this item in the cache.
     *
     * @return mixed The raw cache value or `null if the cache is outdated.
     */
    public function getRaw(string $key)
    {
        return $this->handler->get($key);
    }

    /**
     * Checks if the cache dependency has expired and returns a value.
     *
     * @param string $key The unique key of this item in the cache.
     * @param mixed $value The value of this item in the cache.
     * @param mixed $default Default value to return if the dependency has been changed.
     *
     * @return mixed The cache value or `$default` if the dependency has been changed.
     */
    private function checkAndGetValue(string $key, mixed $value, mixed $default = null): mixed
    {
        if (is_array($value) && isset($value[1]) && $value[1] instanceof CacheItem) {
            [$value, $item] = $value;
            $dependency = $item->dependency();

            if ($item->key() !== $key || ($dependency !== null && $dependency->isChanged($this->cache))) {
                return $default;
            }
        }

        return $value;
    }
}
