<?php

declare(strict_types=1);

namespace Yiisoft\Cache;

use DateInterval;
use Psr\SimpleCache\CacheInterface as PsrSimpleCacheInterface;

/**
 * PrefixedCache decorates any PSR-16 cache to add global prefix. It is added to every cache key so that it is unique
 * globally in the whole cache storage. It is recommended that you set a unique cache key prefix for each application
 * if the same cache storage is being used by different applications.
 *
 * ```php
 * $cache = new PrefixedCache(new ArrayCache(), 'my_app_');
 * $cache->set('answer', 42); // Will set 42 to my_app_answer key.
 * ```
 */
final class PrefixedCache implements PsrSimpleCacheInterface
{
    /**
     * @param PsrSimpleCacheInterface $cache PSR-16 cache to add prefix to.
     * @param string $prefix Prefix to use for all cache keys.
     */
    public function __construct(
        private readonly PsrSimpleCacheInterface $cache,
        private readonly string $prefix
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->get($this->prefix . $key, $default);
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        return $this->cache->set($this->prefix . $key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->cache->delete($this->prefix . $key);
    }

    public function clear(): bool
    {
        return $this->cache->clear();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $prefixedKeys = [];

        foreach ($keys as $key) {
            $prefixedKeys[] = $this->prefix . $key;
        }

        return $this->cache->getMultiple($prefixedKeys, $default);
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $prefixedValues = [];

        /**
         * @var string $key
         */
        foreach ($values as $key => $value) {
            $prefixedValues[$this->prefix . $key] = $value;
        }

        return $this->cache->setMultiple($prefixedValues, $ttl);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $prefixedKeys = [];

        foreach ($keys as $key) {
            $prefixedKeys[] = $this->prefix . $key;
        }

        return $this->cache->deleteMultiple($prefixedKeys);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($this->prefix . $key);
    }
}
