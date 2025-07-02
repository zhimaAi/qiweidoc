<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii Logging Library</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/log/v)](https://packagist.org/packages/yiisoft/log)
[![Total Downloads](https://poser.pugx.org/yiisoft/log/downloads)](https://packagist.org/packages/yiisoft/log)
[![Build status](https://github.com/yiisoft/log/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/log/actions/workflows/build.yml)
[![Code coverage](https://codecov.io/gh/yiisoft/log/graph/badge.svg?token=4CSPCRMGQM)](https://codecov.io/gh/yiisoft/log)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Flog%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/log/master)
[![static analysis](https://github.com/yiisoft/log/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/log/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/log/coverage.svg)](https://shepherd.dev/github/yiisoft/log)

This package provides a [PSR-3](https://www.php-fig.org/psr/psr-3/) compatible logging library. It is used extensively in the
[Yii Framework](https://www.yiiframework.com/) but it can also be used as a separate package.

The logger sends or passes messages to multiple targets. Each target may filter these messages according to their severity level, and category, and then export them to some medium such as a file, an email or a syslog.

## Requirements

- PHP 8.0 or higher.

## Installation

The package can be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/log
```

## General usage

Creating a logger:

```php
/**
 * List of class instances that extend the \Yiisoft\Log\Target abstract class.
 * 
 * @var \Yiisoft\Log\Target[] $targets
 */
$logger = new \Yiisoft\Log\Logger($targets);
```

Writing logs:

```php
$logger->emergency('Emergency message', ['key' => 'value']);
$logger->alert('Alert message', ['key' => 'value']);
$logger->critical('Critical message', ['key' => 'value']);
$logger->warning('Warning message', ['key' => 'value']);
$logger->notice('Notice message', ['key' => 'value']);
$logger->info('Info message', ['key' => 'value']);
$logger->debug('Debug message', ['key' => 'value']);
```

### Message Flushing and Exporting

Log messages are collected and stored in memory. To limit memory consumption, the logger will flush
the recorded messages to the log targets each time a certain number of log messages accumulate.
You can customize this number by calling the `\Yiisoft\Log\Logger::setFlushInterval()` method:

```php
$logger->setFlushInterval(100); // default is 1000
```

Each log target also collects and stores messages in memory.
Message exporting in a target follows the same principle as in the logger.
To change the number of stored messages, call the `\Yiisoft\Log\Target::setExportInterval()` method:

```php
$target->setExportInterval(100); // default is 1000
```

> Note: All message flushing and exporting also occurs when the application ends.

### Logging targets

This package contains two targets:

- `Yiisoft\Log\PsrTarget` - passes log messages to another [PSR-3](https://www.php-fig.org/psr/psr-3/) compatible logger.
- `Yiisoft\Log\StreamTarget` - writes log messages to the specified output stream.

Extra logging targets are implemented as separate packages:

- [Database](https://github.com/yiisoft/log-target-db)
- [Email](https://github.com/yiisoft/log-target-email)
- [File](https://github.com/yiisoft/log-target-file)
- [Syslog](https://github.com/yiisoft/log-target-syslog)

### Context providers

Context providers are used to provide additional context data for log messages. You can define your own context provider
in the `Logger` constructor:

```php
$logger = new \Yiisoft\Log\Logger(contextProvider: $myContextProvider);
```

Out of the box, the following context providers are available:

- `SystemContextProvider` — adds system information (time, memory usage, trace, default category);
- `CommonContextProvider` — adds common data;
- `CompositeContextProvider` — allows combining multiple context providers.

By default, the logger uses the built-in `SystemContextProvider`.

#### `SystemContextProvider`

The `SystemContextProvider` adds the following data to the context:

- `time` — current Unix timestamp with microseconds (float value);
- `trace` — array of call stack information;
- `memory` — memory usage in bytes.
- `category` — category of the log message (always "application").

`Yiisoft\Log\ContextProvider\SystemContextProvider` constructor parameters:

- `traceLevel` — how much call stack information (file name and line number) should be logged for each
  log message. If the traceLevel is greater than 0, a similar number of call stacks will be logged at most. Note that only
  application call stacks are counted.
- `excludedTracePaths` — array of paths to exclude from tracing when tracing is enabled with `traceLevel`.

An example of custom parameters' usage:

```php
$logger = new \Yiisoft\Log\Logger(
    contextProvider: new Yiisoft\Log\ContextProvider\SystemContextProvider(
        traceLevel: 3,
        excludedTracePaths: [
            '/vendor/yiisoft/di',
        ],
    ),
);
```

#### `CommonContextProvider`

The `CommonContextProvider` allows the adding of additional common information to the log context, for example:

```php
$logger = new \Yiisoft\Log\Logger(
    contextProvider: new Yiisoft\Log\ContextProvider\CommonContextProvider([
       'environment' => 'production',
    ]),
);
```

#### `CompositeContextProvider`

The `CompositeContextProvider` allows the combining of multiple context providers into one, for example:

```php
$logger = new \Yiisoft\Log\Logger(
    contextProvider: new Yiisoft\Log\ContextProvider\CompositeContextProvider(
        new Yiisoft\Log\ContextProvider\SystemContextProvider(),
        new Yiisoft\Log\ContextProvider\CommonContextProvider(['environment' => 'production'])
    ),
);
```

## Documentation

- [Yii guide to logging](https://github.com/yiisoft/docs/blob/master/guide/en/runtime/logging.md)
- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is available.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Logging Library is free software. It is released under the terms of the BSD License.
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
