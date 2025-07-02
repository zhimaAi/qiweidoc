<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii Event</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/yii-event/v/stable.png)](https://packagist.org/packages/yiisoft/yii-event)
[![Total Downloads](https://poser.pugx.org/yiisoft/yii-event/downloads.png)](https://packagist.org/packages/yiisoft/yii-event)
[![Build status](https://github.com/yiisoft/yii-event/workflows/build/badge.svg)](https://github.com/yiisoft/yii-event/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/yii-event/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/yii-event/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/yii-event/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/yii-event/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fyii-event%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/yii-event/master)
[![static analysis](https://github.com/yiisoft/yii-event/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/yii-event/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/yii-event/coverage.svg)](https://shepherd.dev/github/yiisoft/yii-event)

This package is a configuration wrapper for the [yiisoft/event-dispatcher](https://github.com/yiisoft/event-dispatcher) package.
It is intended to make event listener declaration simpler than you could ever imagine.
All you need is to use any [PSR-11](https://www.php-fig.org/psr/psr-11/) compatible DI container.

## Requirements

- PHP 8.0 or higher.

## Installation

The package could be installed with composer:

```shell
composer require yiisoft/yii-event
```

## General usage

### DI configuration

You can find default configuration in the [config directory](config):

- [di.php](config/di.php) contains the configuration for the [PSR-14](https://www.php-fig.org/psr/psr-14/) interfaces.
- [di-web.php](config/di-web.php) and [di-console.php](config/di-consle.php) contains the configuration for the `Yiisoft\EventDispatcher\Provider\ListenerCollection`.
- [params-web.php](config/params-web.php) and [params-console.php](config/params-consle.php) contains parameters for `web` and `console` configurations.

All these settings will be used automatically in projects with the [yiisoft/config](https://github.com/yiisoft/config).

If you have custom `events` configuration group name, for example `events-api`, redefine it in the `eventsConfigGroup` params key.

params.php
```php
<?php

declare(strict_types=1);

return [
    'yiisoft/yii-event' => [
        'eventsConfigGroup' => 'events-api',
    ],
]
```

### Event configuration example

The configuration is an array where keys are event names and values are array of handlers:

```php
return [
    EventName::class => [
        // Just a regular closure, it will be called from the Dispatcher "as is".
        static fn (EventName $event) => someStuff($event),
        
        // A regular closure with additional dependency. All the parameters after the first one (the event itself)
        // will be resolved from your DI container within `yiisoft/injector`.
        static fn (EventName $event, DependencyClass $dependency) => someStuff($event),
        
        // An example with a regular callable. If the `staticMethodName` method contains some dependencies,
        // they will be resolved the same way as in the previous example.
        [SomeClass::class, 'staticMethodName'],
        
        // Non-static methods are allowed too. In this case `SomeClass` will be instantiated by your DI container.
        [SomeClass::class, 'methodName'],
        
        // An object of a class with the `__invoke` method implemented
        new InvokableClass(),
        
        // In this case the `InvokableClass` with the `__invoke` method will be instantiated by your DI container
        InvokableClass::class,
        
        // Any definition of an invokable class may be here while your `$container->has('the definition)` 
        'di-alias'
    ],
];
```

The dependency resolving is done in a lazy way: dependencies will not be resolved before the corresponding event
will happen.

### Configuration checker

To help you with event listener configuration validation there is the `ListenerConfigurationChecker`. It is converting
your whole listener config to actual callables at once to validate it. It is intended to be used in development environment
or in tests since it is a resource greedy operation in large projects. An `InvalidEventConfigurationFormatException`
will be thrown if your configuration contains an invalid listener.

Usage example:

```php
$checker->check($configuration->get('events-web'));
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

The Yii Event is free software. It is released under the terms of the BSD License.
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
