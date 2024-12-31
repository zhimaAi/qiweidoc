<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

use Predis\Client;
use Yiisoft\Cache\Redis\RedisCache;

/** @var array $params */

return [
    \Yiisoft\Cache\CacheInterface::class => \Yiisoft\Cache\Cache::class,

    \Psr\SimpleCache\CacheInterface::class => [
        'class' => RedisCache::class,
        '__construct()' => [
            'client' => new Client(
                $params['yiisoft/cache-redis']['uri'],
                ['parameters' => ['password' => $params['yiisoft/cache-redis']['password']]],
            ),
        ],
    ],
];
