<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Metadata;

use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Cache\Exception\InvalidArgumentException;

use function ceil;
use function log;
use function microtime;
use function random_int;
use function sprintf;
use function time;

use const PHP_INT_MAX;

/**
 * CacheItem store the metadata of cache item.
 *
 * @internal
 */
final class CacheItem
{
    private ?int $expiry;
    private float $updated;

    /**
     * @param string $key The key that identifies the cache item.
     * @param int|null $ttl The TTL value of this item. null means infinity.
     * @param Dependency|null $dependency The cache invalidation dependency or null for none.
     */
    public function __construct(
        private string $key,
        ?int $ttl,
        private ?Dependency $dependency
    ) {
        $this->expiry = ($ttl > 0) ? time() + $ttl : $ttl;
        $this->updated = microtime(true);
    }

    /**
     * Updates the metadata of the cache item.
     *
     * @param int|null $expiry The cache expiry. null means infinity.
     * @param Dependency|null $dependency The cache invalidation dependency or null for none.
     */
    public function update(?int $expiry, ?Dependency $dependency): void
    {
        $this->expiry = $expiry;
        $this->dependency = $dependency;
        $this->updated = microtime(true);
    }

    /**
     * Returns a key that identifies the cache item.
     *
     * @return string The key that identifies the cache item.
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * Returns a cache expiry or null that means infinity.
     *
     * @return int|null The cache expiry timestamp or null that means infinity.
     */
    public function expiry(): ?int
    {
        return $this->expiry;
    }

    /**
     * Returns a cache dependency or null if there is none.
     *
     * @return Dependency|null The cache dependency or null if there is none.
     */
    public function dependency(): ?Dependency
    {
        return $this->dependency;
    }

    /**
     * Checks whether the dependency has been changed or whether the cache expired.
     *
     * @param float $beta The value for calculating the range that is used for "Probably early expiration" algorithm.
     * @param CacheInterface $cache The actual cache handler.
     *
     * @return bool Whether the dependency has been changed or whether the cache expired.
     */
    public function expired(float $beta, CacheInterface $cache): bool
    {
        if ($beta < 0) {
            throw new InvalidArgumentException(sprintf(
                'Argument "$beta" must be a positive number, %f given.',
                $beta
            ));
        }

        if ($this->dependency !== null && $this->dependency->isChanged($cache)) {
            return true;
        }

        if ($this->expiry === null) {
            return false;
        }

        if ($this->expiry <= time()) {
            return true;
        }

        $now = microtime(true);
        $delta = ceil(1000 * ($now - $this->updated)) / 1000;
        $expired = $now - $delta * $beta * log(random_int(1, PHP_INT_MAX) / PHP_INT_MAX);

        return $this->expiry <= $expired;
    }
}
