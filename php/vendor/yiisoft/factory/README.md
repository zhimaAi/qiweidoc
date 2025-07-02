<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii Factory</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/factory/v)](https://packagist.org/packages/yiisoft/factory)
[![Total Downloads](https://poser.pugx.org/yiisoft/factory/downloads)](https://packagist.org/packages/yiisoft/factory)
[![Build status](https://github.com/yiisoft/factory/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/factory/actions/workflows/build.yml)
[![Code coverage](https://codecov.io/gh/yiisoft/factory/graph/badge.svg)](https://codecov.io/gh/yiisoft/factory)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Ffactory%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/factory/master)
[![static analysis](https://github.com/yiisoft/factory/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/factory/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/factory/coverage.svg)](https://shepherd.dev/github/yiisoft/factory)

This package provides abstract object factory allowing to create objects by given definition
with dependencies resolved by a [PSR-11](https://www.php-fig.org/psr/psr-11/) container.

## Requirements

- PHP 8.0 or higher.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/factory
```

## General usage

The factory is useful if you need to create objects using [definition syntax](https://github.com/yiisoft/definitions)
and/or want to configure defaults for objects created.

```php
$container = new PSR11DependencyInjectionContainer();
$factoryConfig = [
    EngineInterface::class => [
        'class' => EngineMarkOne::class,
        '__construct()' => [
            'power' => 42,
        ],
    ]
];

$factory = new Factory($container, $factoryConfig);

$one = $factory->create(EngineInterface::class);
$two = $factory->create([
    'class' => EngineInterface::class,
    '__construct()' => [
        'power' => 146,
    ],
]);
```

In the code above we define factory config specifying that when we need `EngineInterface`, an instance of `EngineMarkOne`
will be created with `power` constructor argument equals to 42. We also specify that all the dependencies requested by
the object created should be resolved by `PSR11DependencyInjectionContainer`.

First call to `create()` uses default configuration of `EngineInterface` as is. Second call specifies custom
configuration for `power` constructor argument. In this case, configuration specified is merged with default
configuration overriding its keys when the key name is the same.

### Tuning for production

By default, the factory validates definitions right when they are set. In production environment, it makes sense to
turn it off by passing `false` as a third constructor argument:

```php
$factory = new Factory($container, $factoryConfig, false);
```

### Strict factory

`StrictFactory` differs in that it processes only configured definitions.
When attempting to request an existing class that is not defined in the factory config,
a `NotFoundException` will be thrown.

```php
$container = new PSR11DependencyInjectionContainer();
$factoryConfig = [
    EngineInterface::class => [
        'class' => EngineMarkOne::class,
        '__construct()' => [
            'power' => 42,
        ],
    ]
];

$factory = new Factory($factoryConfig, $container);

$engine = $factory->create(EngineInterface::class);

// Throws `NotFoundException`
$factory->create(EngineMarkOne::class);
```

## Documentation

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Factory is free software. It is released under the terms of the BSD License.
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
