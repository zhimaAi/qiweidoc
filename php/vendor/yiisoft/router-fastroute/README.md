<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii Router FastRoute adapter</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/router-fastroute/v/stable.png)](https://packagist.org/packages/yiisoft/router-fastroute)
[![Total Downloads](https://poser.pugx.org/yiisoft/router-fastroute/downloads.png)](https://packagist.org/packages/yiisoft/router-fastroute)
[![Build status](https://github.com/yiisoft/router-fastroute/workflows/build/badge.svg)](https://github.com/yiisoft/router-fastroute/actions?query=workflow%3Abuild)
[![Code Coverage](https://codecov.io/gh/yiisoft/router-fastroute/graph/badge.svg?token=OKMPXV2N7P)](https://codecov.io/gh/yiisoft/router-fastroute)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Frouter-fastroute%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/router-fastroute/master)
[![static analysis](https://github.com/yiisoft/router-fastroute/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/router-fastroute/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/router-fastroute/coverage.svg)](https://shepherd.dev/github/yiisoft/router-fastroute)

The package provides FastRoute adapter for [Yii Router](https://github.com/yiisoft/router).

## Requirements

- PHP 8.0 or higher.

## Installation

The package could be installed with composer:

```shell
composer require yiisoft/router-fastroute
```

## General usage

The package is not meant to be used separately so check [Yii Router](https://github.com/yiisoft/router) readme for
general usage and [FastRoute](https://github.com/nikic/FastRoute) for pattern syntax.

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

The Yii Router FastRoute adapter is free software. It is released under the terms of the BSD License.
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
