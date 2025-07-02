<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Exception;

final class RemoveCacheException extends CacheException
{
    public function __construct(string $key)
    {
        parent::__construct($key, 'Failed to delete the cache.');
    }
}
