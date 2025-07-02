<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii Input HTTP</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/input-http/v)](https://packagist.org/packages/yiisoft/input-http)
[![Total Downloads](https://poser.pugx.org/yiisoft/input-http/downloads)](https://packagist.org/packages/yiisoft/input-http)
[![Build status](https://github.com/yiisoft/input-http/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/input-http/actions/workflows/build.yml)
[![Code Coverage](https://codecov.io/gh/yiisoft/input-http/branch/master/graph/badge.svg)](https://codecov.io/gh/yiisoft/input-http)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Finput-http%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/input-http/master)
[![static analysis](https://github.com/yiisoft/input-http/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/input-http/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/input-http/coverage.svg)](https://shepherd.dev/github/yiisoft/input-http)
[![psalm-level](https://shepherd.dev/github/yiisoft/input-http/level.svg)](https://shepherd.dev/github/yiisoft/input-http)

The package provides [Yii Hydrator](https://github.com/yiisoft/hydrator) attributes
to get data from [PSR-7 HTTP request](https://www.php-fig.org/psr/psr-7/) and adds extra abilities to middlewares
processed by [Yii Middleware Dispatcher](https://github.com/yiisoft/middleware-dispatcher):

- maps data from PSR-7 HTTP request to PHP DTO representing user input;
- uses Yii Hydrator parameter attributes for resolving middleware parameters.

## Requirements

- PHP 8.1 or higher.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/input-http
```

## General usage

Yii Input HTTP allows to have DTO with attributes like this:

```php
use Yiisoft\Input\Http\AbstractInput;
use Yiisoft\Input\Http\Attribute\Data\FromBody;

#[FromBody]
final class UpdatePostInput extends AbstractInput
{
    public int $id;
    public string $title;
    public string $description = '';
    public string $content;
}
```

and automatically resolve and hydrate it, for example, for arguments like that:

```php
final class UpdatePostController
{
    public function update(UpdatePostInput $post): ResponseInterface
    {
        // ...
        
        /** @var ResponseInterface $response */
        return $response;
    }
}
```

Basic steps:

- Configure [storing request](https://github.com/yiisoft/request-provider?tab=readme-ov-file#general-usage).
- Configure [parameters resolver](docs/guide/en/parameters-resolvers.md).
- Create DTO ([request input](docs/guide/en/request-input.md)).
- Mark DTO properties with [hydrator attributes](docs/guide/en/hydrator-attributes.md) provided by this package.
- Add DTO class name as type hint to a class method argument where you want to it to be resolved.

## Documentation

- Guide: [English](docs/guide/en/README.md), [Português - Brasil](docs/guide/pt-BR/README.md).
- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Input HTTP is free software. It is released under the terms of the BSD License.
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
