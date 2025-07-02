<?php

declare(strict_types=1);

namespace Yiisoft\Cache;

use DateInterval;
use DateTime;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Cache\Exception\InvalidArgumentException;
use Yiisoft\Cache\Exception\RemoveCacheException;
use Yiisoft\Cache\Exception\SetCacheException;
use Yiisoft\Cache\Metadata\CacheItem;
use Yiisoft\Cache\Metadata\CacheItems;

use function is_array;

/**
 * Cache provides support for the data caching, including cache key composition and dependencies, and uses
 * "Probably early expiration" for cache stampede prevention. The actual data caching is performed via
 * PSR-16 {@see \Psr\SimpleCache\CacheInterface} instance passed to constructor.
 * You can use PSR-16 methods via {@see Cache::psr()}.
 *
 * @see \Yiisoft\Cache\CacheInterface
 */
final class Cache implements CacheInterface
{
    /**
     * @var DependencyAwareCache Decorator over the actual cache handler.
     */
    private DependencyAwareCache $psr;

    /**
     * @var CacheItems The items that store the metadata of each cache.
     */
    private CacheItems $items;

    /**
     * @var CacheKeyNormalizer Normalizes the cache key into a valid string.
     */
    private CacheKeyNormalizer $keyNormalizer;

    /**
     * @var int|null The default TTL for a cache entry. null meaning infinity, negative or zero results in the
     * cache key deletion. This value is used by {@see getOrSet()}, if the duration is not explicitly given.
     */
    private ?int $defaultTtl;

    /**
     * @param \Psr\SimpleCache\CacheInterface $handler The actual cache handler.
     * @param DateInterval|int|null $defaultTtl The default TTL for a cache entry.
     * null meaning infinity, negative or zero results in the cache key deletion.
     * This value is used by {@see getOrSet()}, if the duration is not explicitly given.
     */
    public function __construct(\Psr\SimpleCache\CacheInterface $handler, DateInterval|int|null $defaultTtl = null)
    {
        $this->psr = new DependencyAwareCache($this, $handler);
        $this->items = new CacheItems();
        $this->keyNormalizer = new CacheKeyNormalizer();
        $this->defaultTtl = $this->normalizeTtl($defaultTtl);
    }

    public function psr(): \Psr\SimpleCache\CacheInterface
    {
        return $this->psr;
    }

    public function getOrSet(
        mixed $key,
        callable $callable,
        DateInterval|int|null $ttl = null,
        Dependency $dependency = null,
        float $beta = 1.0
    ) {
        $key = $this->keyNormalizer->normalize($key);
        /** @var mixed */
        $value = $this->getValue($key, $beta);

        return $value ?? $this->setAndGet($key, $callable, $ttl, $dependency);
    }

    public function remove(mixed $key): void
    {
        $key = $this->keyNormalizer->normalize($key);

        if (!$this->psr->delete($key)) {
            throw new RemoveCacheException($key);
        }

        $this->items->remove($key);
    }

    /**
     * Gets the cache value.
     *
     * @param string $key The unique key of this item in the cache.
     * @param float $beta The value for calculating the range that is used for "Probably early expiration" algorithm.
     *
     * @return mixed|null The cache value or `null` if the cache is outdated or a dependency has been changed.
     */
    private function getValue(string $key, float $beta): mixed
    {
        if ($this->items->expired($key, $beta, $this)) {
            return null;
        }

        /** @var mixed */
        $value = $this->psr->getRaw($key);

        if (is_array($value) && isset($value[1]) && $value[1] instanceof CacheItem) {
            [$value, $item] = $value;

            if ($item->key() !== $key || $item->expired($beta, $this)) {
                return null;
            }

            $this->items->set($item);
        }

        return $value;
    }

    /**
     * Sets the cache value and metadata, and returns the cache value.
     *
     * @param string $key The unique key of this item in the cache.
     * @param callable $callable The callable or closure that will be used to generate a value to be cached.
     * @psalm-param callable(\Psr\SimpleCache\CacheInterface): mixed $callable
     *
     * @param DateInterval|int|null $ttl The TTL of this value. If not set, default value is used.
     * @param Dependency|null $dependency The dependency of the cache value.
     *
     * @throws InvalidArgumentException Must be thrown if the `$key` or `$ttl` is not a legal value.
     * @throws SetCacheException Must be thrown if the data could not be set in the cache.
     *
     * @return mixed|null The cache value.
     */
    private function setAndGet(
        string $key,
        callable $callable,
        DateInterval|int|null $ttl,
        ?Dependency $dependency
    ): mixed {
        $ttl = $this->normalizeTtl($ttl);
        $ttl ??= $this->defaultTtl;
        /** @var mixed */
        $value = $callable($this->psr);

        if ($dependency !== null) {
            $dependency->evaluateDependency($this);
        }

        $item = new CacheItem($key, $ttl, $dependency);

        if (!$this->psr->set($key, [$value, $item], $ttl)) {
            throw new SetCacheException($key, $value, $item);
        }

        $this->items->set($item);
        return $value;
    }

    /**
     * Normalizes cache TTL handling `null` value and {@see DateInterval} objects.
     *
     * @param DateInterval|int|null $ttl raw TTL.
     *
     * @throws InvalidArgumentException For invalid TTL.
     *
     * @return int|null TTL value as UNIX timestamp or null meaning infinity.
     */
    private function normalizeTtl(DateInterval|int|null $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if ($ttl instanceof DateInterval) {
            return (new DateTime('@0'))
                ->add($ttl)
                ->getTimestamp();
        }

        return $ttl;
    }
}
