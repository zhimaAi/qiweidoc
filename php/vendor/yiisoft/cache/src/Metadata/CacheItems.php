<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Metadata;

use Yiisoft\Cache\CacheInterface;

/**
 * CacheItems store the metadata of each cache item.
 *
 * @internal
 */
final class CacheItems
{
    /**
     * @var array<string, CacheItem>
     */
    private array $items = [];

    /**
     * Checks whether the dependency has been changed or whether the cache expired.
     *
     * @param string $key The key that identifies the cache item.
     * @param float $beta The value for calculating the range that is used for "Probably early expiration" algorithm.
     * @param CacheInterface $cache The actual cache handler.
     *
     * @return bool Whether the dependency has been changed or whether the cache expired.
     */
    public function expired(string $key, float $beta, CacheInterface $cache): bool
    {
        return isset($this->items[$key]) && $this->items[$key]->expired($beta, $cache);
    }

    /**
     * Adds or updates a cache item.
     *
     * @param CacheItem $item The cache item.
     */
    public function set(CacheItem $item): void
    {
        $key = $item->key();

        if (!isset($this->items[$key])) {
            $this->items[$key] = $item;
            return;
        }

        $this->items[$key]->update($item->expiry(), $item->dependency());
    }

    /**
     * Removes a cache item with the specified key.
     *
     * @param string $key The key that identifies the cache item.
     */
    public function remove(string $key): void
    {
        if (isset($this->items[$key])) {
            unset($this->items[$key]);
        }
    }
}
