<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii HTTP Application</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/yii-http/v/stable.png)](https://packagist.org/packages/yiisoft/yii-http)
[![Total Downloads](https://poser.pugx.org/yiisoft/yii-http/downloads.png)](https://packagist.org/packages/yiisoft/yii-http)
[![Build status](https://github.com/yiisoft/yii-http/workflows/build/badge.svg)](https://github.com/yiisoft/yii-http/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/yii-http/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/yii-http/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/yii-http/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/yii-http/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fyii-http%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/yii-http/master)
[![static analysis](https://github.com/yiisoft/yii-http/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/yii-http/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/yii-http/coverage.svg)](https://shepherd.dev/github/yiisoft/yii-http)

This Yii framework package provides the application class, as well as the events
and handlers needed to interact with HTTP. The package is implemented using
[PSR-7](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-7-http-message.md) interfaces.

## Requirements

- PHP 8.0 or higher.

## Installation

The package could be installed with composer:

```shell
composer require yiisoft/yii-http
```

## General usage

In case you use one of the Yii 3 standard application templates, then the application is already configured
there and is running using [yiisoft/yii-runner-http](https://github.com/yiisoft/yii-runner-http) package.

If not, then use one of the HTTP runners that is suitable for your environment:

- [HTTP](https://github.com/yiisoft/yii-runner-http)
- [RoadRunner](https://github.com/yiisoft/yii-runner-roadrunner)

and create an entry script as described in readme of the packages.

If you do not use Yii HTTP runners, then the code for launching the application in your entry script may look like this:

```php
use Yiisoft\Yii\Http\Application;
use Yiisoft\Yii\Http\Handler\NotFoundHandler;
use Yiisoft\Yii\Http\Handler\ThrowableHandler;

/**
 * @var Psr\EventDispatcher\EventDispatcherInterface $eventDispatcher
 * @var Psr\Http\Message\ResponseFactoryInterface $responseFactory
 * @var Psr\Http\Message\ServerRequestInterface $request
 * @var Yiisoft\ErrorHandler\Middleware\ErrorCatcher $errorCatcher
 * @var Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher $dispatcher
 */

$fallbackHandler = new NotFoundHandler($responseFactory);
$application = new Application($dispatcher, $eventDispatcher, $fallbackHandler);

try {
    $application->start();
    $response = $application->handle($request);
    // Emit a response.
} catch (Throwable $throwable) {
    $handler = new ThrowableHandler($throwable);
    $response = $errorCatcher->process($request, $handler);
    // Emit a response with information about the error.
} finally {
    $application->afterEmit($response ?? null);
    $application->shutdown();
}
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

The Yii HTTP Application is free software. It is released under the terms of the BSD License.
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
