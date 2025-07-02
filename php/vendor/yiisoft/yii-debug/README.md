<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii Debug Extension</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/yii-debug/v)](https://packagist.org/packages/yiisoft/yii-debug)
[![Total Downloads](https://poser.pugx.org/yiisoft/yii-debug/downloads)](https://packagist.org/packages/yiisoft/yii-debug)
[![Build status](https://github.com/yiisoft/yii-debug/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/yii-debug/actions/workflows/build.yml)
[![Code coverage](https://codecov.io/gh/yiisoft/yii-debug/graph/badge.svg?token=6FGTORDAP0)](https://codecov.io/gh/yiisoft/yii-debug)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fyii-debug%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/yii-debug/master)
[![static analysis](https://github.com/yiisoft/yii-debug/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/yii-debug/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/yii-debug/coverage.svg)](https://shepherd.dev/github/yiisoft/yii-debug)

This extension provides a debugger for [Yii framework](https://www.yiiframework.com) applications. When this extension is used,
a debugger toolbar will appear at the bottom of every page. The extension also provides
a set of standalone pages to display more detailed debug information.

## Requirements

- PHP 8.1 or higher.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/yii-debug --dev
```

> The debug extension also can be installed without the `--dev` flag if you want to collect data in production.
> Specify the necessary collectors only to reduce functions overriding and improve performance.

## General usage

Once the extension is installed, modify your `config/common/params.php` as follows:

```php
return [
    'yiisoft/yii-debug' => [
        'enabled' => true,
    ],
    // ...
];
```

All included collectors start listen and collect payloads from each HTTP request or console run.

Install both [`yiisoft/yii-debug-api`](https://github.com/yiisoft/yii-debug-api) and [`yiisoft/yii-dev-panel`](https://github.com/yiisoft/yii-dev-panel)
to be able to interact with collected data through UI.

## Documentation

- [Guide](docs/guide/en/README.md)
- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Debug Extension is free software. It is released under the terms of the BSD License.
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
