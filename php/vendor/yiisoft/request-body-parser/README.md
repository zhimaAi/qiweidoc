<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii Request Body Parser</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/request-body-parser/v/stable.png)](https://packagist.org/packages/yiisoft/request-body-parser)
[![Total Downloads](https://poser.pugx.org/yiisoft/request-body-parser/downloads.png)](https://packagist.org/packages/yiisoft/request-body-parser)
[![Build status](https://github.com/yiisoft/request-body-parser/workflows/build/badge.svg)](https://github.com/yiisoft/request-body-parser/actions?query=workflow%3Abuild)
[![Code coverage](https://codecov.io/gh/yiisoft/request-body-parser/graph/badge.svg?token=9TQJWSE5HQ)](https://codecov.io/gh/yiisoft/request-body-parser)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Frequest-body-parser%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/request-body-parser/master)
[![static analysis](https://github.com/yiisoft/request-body-parser/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/request-body-parser/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/request-body-parser/coverage.svg)](https://shepherd.dev/github/yiisoft/request-body-parser)

The package is [PSR-15](https://www.php-fig.org/psr/psr-15/) middleware that allows parsing [PSR-7](https://www.php-fig.org/psr/psr-7/)
server request body selecting the parser according to the server request mime type.

## Requirements

- PHP 7.4 or higher.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/request-body-parser
```

## General usage

1. Add `RequestBodyParser` to your middleware stack.
2. Obtain parsed body via `$request->getParsedBody();`.

By default, it parses `application/json` requests where JSON is in the body. 

You can add your own parser by implementing `ParserInterface`, adding it into the container and registering it within
the middleware:

```php
$requestBodyParser = $requestBodyParser->withParser('application/myformat', MyFormatParser::class);
``` 

## Documentation

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Request Body Parser is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

### Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

### Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
