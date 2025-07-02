<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii Hydrator</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/hydrator/v)](https://packagist.org/packages/yiisoft/hydrator)
[![Total Downloads](https://poser.pugx.org/yiisoft/hydrator/downloads)](https://packagist.org/packages/yiisoft/hydrator)
[![Build status](https://github.com/yiisoft/hydrator/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/hydrator/actions/workflows/build.yml)
[![Code Coverage](https://codecov.io/gh/yiisoft/hydrator/branch/master/graph/badge.svg)](https://codecov.io/gh/yiisoft/hydrator)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fhydrator%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/hydrator/master)
[![static analysis](https://github.com/yiisoft/hydrator/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/hydrator/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/hydrator/coverage.svg)](https://shepherd.dev/github/yiisoft/hydrator)
[![psalm-level](https://shepherd.dev/github/yiisoft/hydrator/level.svg)](https://shepherd.dev/github/yiisoft/hydrator)

The package provides a way to create and hydrate objects from a set of raw data.

Features are:

- supports properties of any visibility;
- uses constructor arguments to create objects;
- resolves dependencies when creating objects using [PSR-11](https://www.php-fig.org/psr/psr-11/) compatible DI container
  provided;
- supports nested objects;
- supports mapping;
- allows fine-tuning hydration via PHP attributes.

## Requirements

- PHP 8.1 or higher.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/hydrator
```

## General usage

To hydrate existing object:

```php
use Yiisoft\Hydrator\Hydrator;

$hydrator = new Hydrator();
$hydrator->hydrate($object, $data);
```

To create a new object and fill it with the data:

```php
use Yiisoft\Hydrator\Hydrator;

$hydrator = new Hydrator();
$object = $hydrator->create(MyClass::class, $data);
```

To pass arguments to the constructor of a nested object, use nested array or dot-notation:

```php
final class Engine
{
    public function __construct(
        private string $name,
    ) {}
}

final class Car
{
    public function __construct(
        private string $name,
        private Engine $engine,
    ) {}
}

// nested array
$object = $hydrator->create(Car::class, [
    'name' => 'Ferrari',
    'engine' => [
        'name' => 'V8',
    ]
]);

// or dot-notation
$object = $hydrator->create(Car::class, [
    'name' => 'Ferrari',
    'engine.name' => 'V8',
]);
```

That would pass the `name` constructor argument of the `Car` object and create a new `Engine` object for `engine`
argument passing `V8` as the `name` argument to its constructor.

## Documentation

- Guide: [English](docs/guide/en/README.md), [Português - Brasil](docs/guide/pt-BR/README.md), [Русский](docs/guide/ru/README.md)
- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Hydrator is free software. It is released under the terms of the BSD License.
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
