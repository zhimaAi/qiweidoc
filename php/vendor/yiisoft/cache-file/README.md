<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii Cache Library - File Handler</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/cache-file/v/stable.png)](https://packagist.org/packages/yiisoft/cache-file)
[![Total Downloads](https://poser.pugx.org/yiisoft/cache-file/downloads.png)](https://packagist.org/packages/yiisoft/cache-file)
[![Build status](https://github.com/yiisoft/cache-file/workflows/build/badge.svg)](https://github.com/yiisoft/cache-file/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/cache-file/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/cache-file/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/cache-file/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/cache-file/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fcache-file%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/cache-file/master)
[![static analysis](https://github.com/yiisoft/cache-file/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/cache-file/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/cache-file/coverage.svg)](https://shepherd.dev/github/yiisoft/cache-file)

This package implements file-based [PSR-16](https://www.php-fig.org/psr/psr-16/) cache.

## Requirements

- PHP 8.0 or higher.

## Installation

The package could be installed with composer:

```shell
composer require yiisoft/cache-file
```

## Configuration

When creating an instance of `\Yiisoft\Cache\File\FileCache`, you must specify
the path to the base directory in which the cache files will be stored:

```php
$cache = new \Yiisoft\Cache\File\FileCache('/path/to/directory');
```

Change the permission to be set for newly created directories:

```php
$cache = new \Yiisoft\Cache\File\FileCache('/path/to/directory', 0777); // default is 0775
```

This value will be used by PHP `chmod()` function. No umask will be applied. Defaults to 0775,
meaning the directory is read-writable by an owner and group, but read-only for other users.

Change the suffix of the cache files:

```php
$cache = $cache->withFileSuffix('.txt'); // default is '.bin'
```

Change the permission to be set for newly created cache files:

```php
$cache = $cache->withFileMode(0644); // default is null
```

This value will be used by PHP `chmod()` function. No umask will be applied.
If not set, the permission will be determined by the current environment.

Change the level of sub-directories to store cache files:

```php
$cache = $cache->withDirectoryLevel(3); // default is 1
```

If the system has huge number of cache files (e.g. one million), you may use a bigger
value (usually no bigger than 3). Using sub-directories is mainly to ensure the file
system is not over burdened with a single directory having too many files.

Change the probability of performing garbage collection when storing a piece of data in the cache:

```php
$cache = $cache->withGcProbability(1000); // default is 10
```

The probability (parts per million) that garbage collection (GC) should be performed when
storing a piece of data in the cache. Defaults to 10, meaning 0.001% chance. This number
should be between 0 and 1000000. A value 0 means no GC will be performed at all.

## General usage

The package does not contain any additional functionality for interacting with the cache,
except those defined in the [PSR-16](https://www.php-fig.org/psr/psr-16/) interface.

```php
$cache = new \Yiisoft\Cache\File\FileCache('/path/to/directory');
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

## Testing

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```shell
./vendor/bin/infection
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

### Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

### Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)

## License

The Yii Cache Library - File Handler is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).
