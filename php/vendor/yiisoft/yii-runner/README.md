<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii application runner</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/yii-runner/v/stable.png)](https://packagist.org/packages/yiisoft/yii-runner)
[![Total Downloads](https://poser.pugx.org/yiisoft/yii-runner/downloads.png)](https://packagist.org/packages/yiisoft/yii-runner)
[![Build status](https://github.com/yiisoft/yii-runner/workflows/build/badge.svg)](https://github.com/yiisoft/yii-runner/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/yii-runner/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/yii-runner/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/yii-runner/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/yii-runner/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fyii-runner%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/yii-runner/master)
[![static analysis](https://github.com/yiisoft/yii-runner/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/yii-runner/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/yii-runner/coverage.svg)](https://shepherd.dev/github/yiisoft/yii-runner)

The package defines Yii application runner. A runner hides application initialization details making configuration
process easier.

## Requirements

- PHP 8.0 or higher.

## Installation

The package could be installed with composer:

```shell
composer require yiisoft/yii-runner
```

## General usage

Install one of the adapters:

- [Console](https://github.com/yiisoft/yii-runner-console)
- [HTTP](https://github.com/yiisoft/yii-runner-http)
- [RoadRunner](https://github.com/yiisoft/yii-runner-roadrunner)

Instantiate and run it in an entry script:

```php
<?php

declare(strict_types=1);

use Yiisoft\Yii\Runner\Http\HttpApplicationRunner;

require_once __DIR__ . '/autoload.php';

(new HttpApplicationRunner(
    rootPath: __DIR__, 
    debug: $_ENV['YII_DEBUG'],
    environment: $_ENV['YII_ENV']
))->run();
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

The Yii Runner is free software. It is released under the terms of the BSD License.
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
