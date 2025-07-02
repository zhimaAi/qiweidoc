<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii Logging Library - File Target</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/log-target-file/v/stable.png)](https://packagist.org/packages/yiisoft/log-target-file)
[![Total Downloads](https://poser.pugx.org/yiisoft/log-target-file/downloads.png)](https://packagist.org/packages/yiisoft/log-target-file)
[![Build status](https://github.com/yiisoft/log-target-file/workflows/build/badge.svg)](https://github.com/yiisoft/log-target-file/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/log-target-file/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/log-target-file/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/log-target-file/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/log-target-file/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Flog-target-file%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/log-target-file/master)
[![static analysis](https://github.com/yiisoft/log-target-file/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/log-target-file/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/log-target-file/coverage.svg)](https://shepherd.dev/github/yiisoft/log-target-file)

This package provides the File target for the [yiisoft/log](https://github.com/yiisoft/log). The target:

- records log messages in a file
- allows you to configure log files rotation
- provides the ability to compress rotated log files

## Requirements

- PHP 8.0 or higher.

## Installation

The package could be installed with composer:

```
composer require yiisoft/log-target-file --prefer-dist
```

## General usage

Creating a rotator:

```php
$rotator = new \Yiisoft\Log\Target\File\FileRotator(
    $maxFileSize,
    $maxFiles,
    $fileMode,
    $compressRotatedFiles
);
```

- `$maxFileSize (int)` - The maximum file size, in kilo-bytes. Defaults to `10240`, meaning 10MB.
- `$maxFiles (int)` - The number of files used for rotation. Defaults to `5`.
- `$fileMode (int|null)` - The permission to be set for newly created files. Defaults to `null`.
- `$compressRotatedFiles (bool)` - Whether to compress rotated files with gzip. Defaults to `false`.

Creating a target:

```php
$fileTarget = new \Yiisoft\Log\Target\File\FileTarget(
    $logFile,
    $rotator,
    $dirMode,
    $fileMode
);
```

- `$logFile (string)` - The log file path. Defaults to `/tmp/app.log`.
- `$rotator (\Yiisoft\Log\Target\File\FileRotatorInterface|null)` - Defaults to `null`,
  which means that log files will not be rotated.
- `$dirMode (int)` - The permission to be set for newly created directories. Defaults to `0775`.
- `$fileMode (int|null)` - The permission to be set for newly created log files. Defaults to `null`.

Creating a logger:

```php
$logger = new \Yiisoft\Log\Logger([$fileTarget]);
```

For a description of using the logger, see the [yiisoft/log](https://github.com/yiisoft/log) package.

For use in the [Yii framework](http://www.yiiframework.com/), see the configuration files:

- [`config/common.php`](https://github.com/yiisoft/log-target-file/blob/master/config/common.php)
- [`config/params.php`](https://github.com/yiisoft/log-target-file/blob/master/config/params.php)

See [Yii guide to logging](https://github.com/yiisoft/docs/blob/master/guide/en/runtime/logging.md) for more info.

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

The Yii Logging Library - File Target is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).
