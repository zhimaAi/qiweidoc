<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii Caching Library - Redis Handler</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/cache-redis/v/stable.png)](https://packagist.org/packages/yiisoft/cache-redis)
[![Total Downloads](https://poser.pugx.org/yiisoft/cache-redis/downloads.png)](https://packagist.org/packages/yiisoft/cache-redis)
[![Build status](https://github.com/yiisoft/cache-redis/workflows/build/badge.svg)](https://github.com/yiisoft/cache-redis/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/cache-redis/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/cache-redis/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/cache-redis/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/cache-redis/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fcache-redis%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/cache-redis/master)
[![static analysis](https://github.com/yiisoft/cache-redis/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/cache-redis/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/cache-redis/coverage.svg)](https://shepherd.dev/github/yiisoft/cache-redis)

This package provides the [Redis](https://redis.io/) handler and
implements [PSR-16](https://www.php-fig.org/psr/psr-16/) cache.

## Requirements

- PHP 8.0 or higher.

## Installation

The package could be installed with composer:

```shell
composer require yiisoft/cache-redis --prefer-dist
```

## General usage

For more information about the client instance and connection configuration,
see the documentation of the [predis/predis](https://github.com/predis/predis) package.

```php
/**
 * @var \Predis\ClientInterface $client Predis client instance to use.
 */
$cache = new \Yiisoft\Cache\Redis\RedisCache($client);
```

The package does not contain any additional functionality for interacting with the cache,
except those defined in the [PSR-16](https://www.php-fig.org/psr/psr-16/) interface.

```php
$parameters = ['user_id' => 42];
$key = 'demo';

// try retrieving $data from cache
$data = $cache->get($key);

if ($data === null) {
    // $data is not found in cache, calculate it from scratch
    $data = calculateData($parameters);
    
    // store $data in cache for an hour so that it can be retrieved next time
    $cache->set($key, $data, 3600);
}

// $data is available here
```

In order to delete value you can use:

```php
$cache->delete($key);
// Or all cache
$cache->clear();
```

To work with values in a more efficient manner, batch operations should be used:

- `getMultiple()`
- `setMultiple()`
- `deleteMultiple()`

This package can be used as a cache handler for the [Yii Caching Library](https://github.com/yiisoft/cache).

## Redis cluster supported

The package implements [Redis cluster](https://redis.io/docs/management/scaling/) support via `Predis` package.

For example, if your cluster configuration has three master nodes and three slave nodes, your client configuration might look like this:

```php
$client = new \Predis\Client([
        ['host' => 'redis-node-1', 'port' => 'redis-node-port-1',],
        ['host' => 'redis-node-2', 'port' => 'redis-node-port-2',],
        ['host' => 'redis-node-3', 'port' => 'redis-node-port-3',],
        ['host' => 'redis-node-4', 'port' => 'redis-node-port-4',],
        ['host' => 'redis-node-5', 'port' => 'redis-node-port-5',],
        ['host' => 'redis-node-6', 'port' => 'redis-node-port-6',],
    ],
    [
        'cluster' => 'redis',
        'parameters' => [
            'password' => 'Password',
        ],
    ]
);
$cache = new \Yiisoft\Cache\Redis\RedisCache($client);
```
Predis will route commands on its own when specifying Redis nodes in the cluster to the appropriate nodes depending on the keys that are specified in the commands.

You can implement `\Predis\Distribution\DistributorInterface` to create their own distributors used by the client to distribute keys among a cluster of servers.

Then your config might look like this:

```php
$client = new \Predis\Client([
        ['host' => 'redis-node-1', 'port' => 'redis-node-port-1',],
        ['host' => 'redis-node-2', 'port' => 'redis-node-port-2',],
        ['host' => 'redis-node-3', 'port' => 'redis-node-port-3',],
        ['host' => 'redis-node-4', 'port' => 'redis-node-port-4',],
        ['host' => 'redis-node-5', 'port' => 'redis-node-port-5',],
        ['host' => 'redis-node-6', 'port' => 'redis-node-port-6',],
    ],
    [
        'cluster' => static function () {
            $distributor = new \CustomDistributor(); // Your custom distributor
            $strategy = new \Predis\Cluster\PredisStrategy($distributor);

            return new \Predis\Connection\Cluster\PredisCluster($strategy);
        },
        'parameters' => [
            'password' => 'Password',
        ],
    ]
);
```

## Testing

> The tests use a connection to a running Redis cluster. If you are not using Docker, you must start the cluster yourself before running the tests.

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework with
[Infection Static Analysis Plugin](https://github.com/Roave/infection-static-analysis-plugin). To run it:

```shell
./vendor/bin/roave-infection-static-analysis-plugin
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

## Testing in Docker

### Prepare

```bash
# {{ v }} = 8.0, 8.1, 8.2. Default PHP 8.1
make build v=8.1
```

### Unit testing

```bash
# {{ v }} = 8.0, 8.1, 8.2. Default PHP 8.1
make test v=8.1
```

### Mutation testing

```bash
# {{ v }} = 8.0, 8.1, 8.2. Default PHP 8.1
make mutation-test v=8.0
```

## License

The Yii Caching Library - Redis Handler is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
