<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii Caching Library</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/cache/v/stable.png)](https://packagist.org/packages/yiisoft/cache)
[![Total Downloads](https://poser.pugx.org/yiisoft/cache/downloads.png)](https://packagist.org/packages/yiisoft/cache)
[![Build status](https://github.com/yiisoft/cache/workflows/build/badge.svg)](https://github.com/yiisoft/cache/actions?query=workflow%3Abuild)
[![Code Coverage](https://codecov.io/gh/yiisoft/cache/branch/master/graph/badge.svg)](https://codecov.io/gh/yiisoft/cache)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fcache%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/cache/master)
[![static analysis](https://github.com/yiisoft/cache/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/cache/actions?query=workflow%3A%22static+analysis%22)
[![psalm-level](https://shepherd.dev/github/yiisoft/cache/level.svg)](https://shepherd.dev/github/yiisoft/cache)
[![type-coverage](https://shepherd.dev/github/yiisoft/cache/coverage.svg)](https://shepherd.dev/github/yiisoft/cache)

This library is a wrapper around [PSR-16](https://www.php-fig.org/psr/psr-16/) compatible caching libraries
providing own features. It is used in [Yii Framework](https://www.yiiframework.com/) but is usable separately.

## Features

- Built on top of PSR-16, it can use any PSR-16 cache as a handler.
- Ability to set default TTL and key prefix per cache instance.
- Provides a built-in behavior to cache stampede prevention.
- Adds cache invalidation dependencies on top of PSR-16.

## Requirements

- PHP 8.0 or higher.
- `JSON` PHP extension.
- `Mbstring` PHP extension.
  
## Installation

The package could be installed with composer:

```shell
composer require yiisoft/cache
```

## Configuration

There are two ways to get cache instance. If you need PSR-16 instance, you can simply create it:

```php
$arrayCache = new \Yiisoft\Cache\ArrayCache();
```

In order to set a global key prefix:

```php
$arrayCacheWithPrefix = new \Yiisoft\Cache\PrefixedCache(new \Yiisoft\Cache\ArrayCache(), 'myapp_');
```

If you need a simpler yet more powerful way to cache values based on recomputation callbacks use `getOrSet()`
and `remove()`, additional features such as invalidation dependencies and "Probably early expiration" 
stampede prevention, you should wrap PSR-16 cache instance with `\Yiisoft\Cache\Cache`:

```php
$cache = new \Yiisoft\Cache\Cache($arrayCache);
```

Set a default TTL:

```php
$cache = new \Yiisoft\Cache\Cache($arrayCache, 60 * 60); // 1 hour
```

## General usage

Typical PSR-16 cache usage is the following:

```php
$cache = new \Yiisoft\Cache\ArrayCache();
$parameters = ['user_id' => 42];
$key = 'demo';

// Try retrieving $data from cache.
$data = $cache->get($key);
if ($data === null) {
    // $data is not found in cache, calculate it from scratch.
    $data = calculateData($parameters);
    
    // Store $data in cache for an hour so that it can be retrieved next time.
    $cache->set($key, $data, 3600);
}

// $data is available here.
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

When using extended cache i.e. PSR-16 cache wrapped with `\Yiisoft\Cache\Cache`, you can use alternative syntax that
is less repetitive:

```php
$cache = new \Yiisoft\Cache\Cache(new \Yiisoft\Cache\ArrayCache());
$key = ['top-products', $count = 10];

$data = $cache->getOrSet($key, function (\Psr\SimpleCache\CacheInterface $cache) use ($count) {
    return getTopProductsFromDatabase($count);
}, 3600);
```

Normalization of the key occurs using the `Yiisoft\Cache\CacheKeyNormalizer`.

In order to delete value you can use:

```php
$cache->remove($key);
```

You can use PSR-16 methods the following way, but remember that getting and
setting the cache separately violates the "Probably early expiration" algorithm.

```php
$value = $cache
    ->psr()
    ->get('myKey');
```

### Invalidation dependencies

When using `\Yiisoft\Cache\Cache`, additionally to TTL for `getOrSet()` method you can specify a dependency
that may trigger cache invalidation. Below is an example using tag dependency:

```php
/**
 * @var callable $callable
 * @var \Yiisoft\Cache\CacheInterface $cache
 */

use Yiisoft\Cache\Dependency\TagDependency;

// Set multiple cache values marking both with a tag.
$cache->getOrSet('item_42_price', $callable, null, new TagDependency('item_42'));
$cache->getOrSet('item_42_total', $callable, 3600, new TagDependency('item_42'));

// Trigger invalidation by tag.
TagDependency::invalidate($cache, 'item_42');
```

Other dependencies:

- `Yiisoft\Cache\Dependency\CallbackDependency` - invalidates the cache when callback result changes.
- `Yiisoft\Cache\Dependency\FileDependency` - invalidates the cache based on file modification time.
- `Yiisoft\Cache\Dependency\ValueDependency` - invalidates the cache when specified value changes.

You may combine multiple dependencies using `Yiisoft\Cache\Dependency\AnyDependency`
or `Yiisoft\Cache\Dependency\AllDependencies`.

In order to implement your own dependency extend from `Yiisoft\Cache\Dependency\Dependency`.

### Cache stampede prevention

[A cache stampede](https://en.wikipedia.org/wiki/Cache_stampede) is a type of cascading failure that can occur when massively
parallel computing systems with caching mechanisms come under very high load. This behaviour is sometimes also called dog-piling.
The `\Yiisoft\Cache\Cache` uses a built-in "Probably early expiration" algorithm that prevents cache stampede.
This algorithm randomly fakes a cache miss for one user while others are still served the cached value.
You can control its behavior with the fifth optional parameter of `getOrSet()`, which is a float value called `$beta`.
By default, beta is `1.0`, which is sufficient in most cases. The higher the value the earlier cache will be re-created.

```php
/**
 * @var mixed $key
 * @var callable $callable
 * @var \DateInterval $ttl
 * @var \Yiisoft\Cache\CacheInterface $cache
 * @var \Yiisoft\Cache\Dependency\Dependency $dependency
 */

$beta = 2.0;
$cache->getOrSet($key, $callable, $ttl, $dependency, $beta);
```

### Cache handlers

> Below the handler refers to the implementations of [PSR-16](https://www.php-fig.org/psr/psr-16/).

This package contains two handlers:

- `Yiisoft\Cache\ArrayCache` - provides caching for the current request only by storing the values in an array.
- `Yiisoft\Cache\NullCache` - does not cache anything reporting success for all methods calls.

Extra cache handlers are implemented as separate packages:

- [APCu](https://github.com/yiisoft/cache-apcu)
- [Database](https://github.com/yiisoft/cache-db)
- [File](https://github.com/yiisoft/cache-file)
- [Memcached](https://github.com/yiisoft/cache-memcached)
- [Redis](https://github.com/yiisoft/cache-redis)
- [Wincache](https://github.com/yiisoft/cache-wincache)

## Testing

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

## License

The Yii Caching Library is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3ru)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
