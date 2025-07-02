<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Exception;

use Yiisoft\Cache\Metadata\CacheItem;

final class SetCacheException extends CacheException
{
    public function __construct(
        string $key,
        private mixed $value,
        private CacheItem $item
    ) {
        parent::__construct($key, 'Failed to store the value in the cache.');
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getItem(): CacheItem
    {
        return $this->item;
    }
}
