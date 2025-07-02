<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Dependency;

use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Exception\InvalidArgumentException;

use function json_encode;
use function json_last_error_msg;
use function md5;
use function microtime;

/**
 * TagDependency associates a cached value with one or multiple {@see TagDependency::$tags}.
 *
 * By calling {@see TagDependency::invalidate()}, you can invalidate all
 * cached values that are associated with the specified tag name(s).
 *
 * ```php
 * // setting multiple cache keys to store data forever and tagging them with "user-123"
 * $cache->getOrSet('user_42_profile', '', null, new TagDependency('user-123'));
 * $cache->getOrSet('user_42_stats', '', null, new TagDependency('user-123'));
 *
 *  // setting a cache key to store data and tagging them with "user-123" with the specified TTL for the tag
 * $cache->getOrSet('user_42_profile', '', null, new TagDependency('user-123', 3600));
 *
 * // invalidating all keys tagged with "user-123"
 * TagDependency::invalidate($cache, 'user-123');
 * ```
 */
final class TagDependency extends Dependency
{
    /**
     * @var array List of tag names for this dependency.
     */
    private array $tags;

    /**
     * @var int|null The TTL value of this item. null means infinity.
     */
    private ?int $ttl;

    /**
     * @param array|string $tags List of tag names for this dependency.
     * For a single tag, you may specify it as a string.
     * @param int|null $ttl The TTL value of this item. null means infinity.
     */
    public function __construct(array|string $tags, int $ttl = null)
    {
        $this->tags = (array) $tags;

        if ($ttl !== null && $ttl < 1) {
            throw new InvalidArgumentException(
                'TTL must be a positive number or null, to invalidate tags, use the'
                . ' static `\Yiisoft\Cache\Dependency\TagDependency::invalidate()` method.',
            );
        }

        $this->ttl = $ttl;
    }

    protected function generateDependencyData(CacheInterface $cache): array
    {
        if (empty($this->tags)) {
            return [];
        }

        $tags = [];

        foreach ($this->getTagsData($cache) as $tag => $time) {
            $tags[$tag] = $time ?? microtime();
        }

        $cache
            ->psr()
            ->setMultiple($tags, $this->ttl);

        return $tags;
    }

    public function isChanged(CacheInterface $cache): bool
    {
        if (empty($this->tags)) {
            return $this->data !== [];
        }

        return $this->data !== $this->getTagsData($cache);
    }

    /**
     * Invalidates all of the cached values that are associated with any of the specified {@see tags}.
     *
     * @param CacheInterface $cache The cache component that caches the values.
     * @param array|string $tags List of tag names.
     */
    public static function invalidate(CacheInterface $cache, array|string $tags): void
    {
        $cache
            ->psr()
            ->deleteMultiple(self::buildCacheKeys((array) $tags));
    }

    /**
     * Builds a normalized cache key from a given tag, making sure it is short enough and safe
     * for any particular cache storage.
     *
     * @param string $tag The tag name.
     *
     * @return string The cache key.
     */
    private static function buildCacheKey(string $tag): string
    {
        $jsonTag = json_encode([self::class, $tag]);

        if ($jsonTag === false) {
            throw new InvalidArgumentException('Invalid tag. ' . json_last_error_msg() . '.');
        }

        return md5($jsonTag);
    }

    /**
     * Builds array of keys from a given tags.
     *
     * @return string[]
     */
    private static function buildCacheKeys(array $tags): array
    {
        $keys = [];

        /** @var mixed $tag */
        foreach ($tags as $tag) {
            $keys[] = self::buildCacheKey((string) $tag);
        }

        return $keys;
    }

    /**
     * Gets the tags data from the cache storage.
     *
     * @psalm-return array<array-key, string|null>
     */
    private function getTagsData(CacheInterface $cache): array
    {
        /** @psalm-var array<array-key, string|null> */
        return $this->iterableToArray($cache
                ->psr()
                ->getMultiple(self::buildCacheKeys($this->tags)));
    }
}
