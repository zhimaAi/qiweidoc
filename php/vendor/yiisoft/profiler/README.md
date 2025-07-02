<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii Profiler</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/profiler/v/stable.png)](https://packagist.org/packages/yiisoft/profiler)
[![Total Downloads](https://poser.pugx.org/yiisoft/profiler/downloads.png)](https://packagist.org/packages/yiisoft/profiler)
[![Build status](https://github.com/yiisoft/profiler/workflows/build/badge.svg)](https://github.com/yiisoft/profiler/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/profiler/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/profiler/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/profiler/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/profiler/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fprofiler%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/profiler/master)
[![static analysis](https://github.com/yiisoft/profiler/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/profiler/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/profiler/coverage.svg)](https://shepherd.dev/github/yiisoft/profiler)

The package provides an ability to record performance profiles.

## Requirements

- PHP 8.0 or higher.

## Installation

The package could be installed via composer:

```shell
composer require --dev yiisoft/profiler
```

If you use this package with [Yii Config](https://github.com/yiisoft/config) and there will be no `@runtime` alias in `yiisoft/aliases` configuration defined, application will throw "Invalid path alias" error.

To solve it, add the `@runtime` alias to the `yiisoft/aliases` configuration in `params.php`:

```php
return [
    //...
    'yiisoft/aliases' => [
        'aliases' => [
            //...
            '@runtime' => '@root/runtime'
        ],
    ],
    //...
];
```

## General usage

### Profiling

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Psr\Log\NullLogger;
use Yiisoft\Profiler\Profiler;
use Yiisoft\Profiler\Target\LogTarget;

$logger = new NullLogger();
$target = new LogTarget($logger);

$profiler = new Profiler($logger, [$target]);

$profiler->begin('test');
//...some code
$profiler->end('test');

```

Nested profiling

```php
$profiler->begin('test');
//...some code
    $profiler->begin('test');
    //...some code
    $profiler->end('test');
//...some code
$profiler->end('test');
```

### Getting profiler messages

```php
$messages = $profiler->getMessages(); 
print_r($messages);
```

Output

```
Array
(
    [0] => Yiisoft\Profiler\Message Object
        (
            [level:Yiisoft\Profiler\Message:private] => application
            [token:Yiisoft\Profiler\Message:private] => test
            [context:Yiisoft\Profiler\Message:private] => Array
                (
                    [token] => test
                    [category] => application
                    [nestedLevel] => 0
                    [time] => 1614703708.4328
                    [beginTime] => 1614703708.4328
                    [beginMemory] => 7696440
                    [endTime] => 1614703708.4331
                    [endMemory] => 7702392
                    [duration] => 0.0003058910369873
                    [memoryDiff] => 5952
                )

        )

)

```

### Find profiler messages with a given token

```php
$profiler->begin('test');
//...some code
$profiler->end('test');
$profiler->begin('another test');
//...some code
$profiler->end('another test');

$messages = $profiler->findMessages('another test');
print_r($messages);
```

Output

````
Array
(
    [0] => Yiisoft\Profiler\Message Object
        (
            [level:Yiisoft\Profiler\Message:private] => application
            [token:Yiisoft\Profiler\Message:private] => another test
            [context:Yiisoft\Profiler\Message:private] => Array
                (
                    [token] => another test
                    [category] => application
                    [nestedLevel] => 0
                    [time] => 1614703716.4328
                    [beginTime] => 1614703716.4328
                    [beginMemory] => 7696440
                    [endTime] => 1614703716.4331
                    [endMemory] => 7702392
                    [duration] => 0.0003058910369873
                    [memoryDiff] => 5952
                )

        )

)
````

### Saving messages to storage

```php
// obtain profiler
$profiler = getProfiler();
// send profiler messages to targets
$profiler->flush();
```

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

The Yii Profiler is free software. It is released under the terms of the BSD License.
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
