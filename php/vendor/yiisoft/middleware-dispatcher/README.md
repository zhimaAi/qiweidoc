<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii Middleware Dispatcher</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/middleware-dispatcher/v/stable.png)](https://packagist.org/packages/yiisoft/middleware-dispatcher)
[![Total Downloads](https://poser.pugx.org/yiisoft/middleware-dispatcher/downloads.png)](https://packagist.org/packages/yiisoft/middleware-dispatcher)
[![Build status](https://github.com/yiisoft/middleware-dispatcher/workflows/build/badge.svg)](https://github.com/yiisoft/middleware-dispatcher/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/middleware-dispatcher/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/middleware-dispatcher/?branch=master)
[![Code Coverage](https://codecov.io/gh/yiisoft/middleware-dispatcher/branch/master/graph/badge.svg)](https://codecov.io/gh/yiisoft/middleware-dispatcher)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fmiddleware-dispatcher%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/middleware-dispatcher/master)
[![static analysis](https://github.com/yiisoft/middleware-dispatcher/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/middleware-dispatcher/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/middleware-dispatcher/coverage.svg)](https://shepherd.dev/github/yiisoft/middleware-dispatcher)
[![psalm-level](https://shepherd.dev/github/yiisoft/middleware-dispatcher/level.svg)](https://shepherd.dev/github/yiisoft/middleware-dispatcher)

The package is a [PSR-15](https://www.php-fig.org/psr/psr-15/) middleware dispatcher. Given a set of middleware and a
request instance, dispatcher executes it produces a response instance.

## Requirements

- PHP 8.0 or higher.

## Installation

The package could be installed with composer:

```shell
composer require yiisoft/middleware-dispatcher
```

## General usage

To use a dispatcher, you need to create it first:

```php
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;

$dispatcher = new MiddlewareDispatcher(
    new MiddlewareFactory($diContainer),
    $eventDispatcher
);
```

In the above `$diContainer` is an instance of [PSR-11](https://www.php-fig.org/psr/psr-11/) `\Psr\Container\ContainerInterface`
and `$eventDispatcher` is an instance of [PSR-14](https://www.php-fig.org/psr/psr-14/) `Psr\EventDispatcher\EventDispatcherInterface`.

After dispatcher instance obtained, it should be fed with some middleware: 

```php
$dispatcher = $dispatcher->withMiddlewares([
    TeapotAccessChecker::class,
    static function (): ResponseInterface {
        return new Response(418);
    },
]);
```

In the above we have used a callback. Overall the following options are available:

- A controller handler action in format `[TestController::class, 'index']`. `TestController` instance will be created and
  `index()` method will be executed.
- A name of PSR-15 middleware class. The middleware instance will be obtained from container.
- A name of PSR-15 request handler class. The request handler instance will be obtained from container and executed.
- A name or instance of invokable class. If the name of invokable class is provided, the instance will be 
  obtained from container and executed.
- A function returning a middleware such as
  ```php
  static function (): MiddlewareInterface {
      return new TestMiddleware();
  }
  ```
  The middleware returned will be executed.
- A callback `function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface`.
- An array definition (see [syntax](https://github.com/yiisoft/definitions#arraydefinition)) of middleware:
  ```php
  [
      'class' => MyMiddleware::class,
      '__construct()' => [
          'someVar' => 42,
      ],
  ]
  ``` 

For handler action and callable typed parameters are automatically injected using dependency injection container.
Current request and handler could be obtained by type-hinting for `ServerRequestInterface` and `RequestHandlerInterface`.

After middleware set is defined, you can do the dispatching: 

```php
$request = new ServerRequest('GET', '/teapot');
$response = $dispatcher->dispatch($request, $this->getRequestHandler());
```

Given a request dispatcher executes middleware in the set and produces response. First specified middleware will be
executed first. For each middleware
`\Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware` and `\Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware`
events are triggered.

### Creating your own implementation of parameters resolver

Parameters resolver could be customized by providing your own `ParametersResolverInterface` implementation:

```php
use \Psr\Http\Message\ServerRequestInterface;
use \Yiisoft\Middleware\Dispatcher\ParametersResolverInterface;

class CoolParametersResolver implements ParametersResolverInterface
{
    public function resolve(array $parameters, ServerRequestInterface $request): MiddlewareInterface
    {
        $resolvedParameters = [];
        foreach ($parameters as $name => $parameter) {
            if ($request->getAttribute($name) !== null) {
                $resolvedParameters[$name] = $request->getAttribute($name)
            }
        }
        
        return $resolvedParameters;
    }
}
```

Then it could be used like the following:

```php
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;

$dispatcher = new MiddlewareDispatcher(
    new MiddlewareFactory($diContainer, new CoolParametersResolver()),
    $eventDispatcher
);
```

To combine several parameters' resolvers use `CompositeParametersResolver`:

```php
use Yiisoft\Middleware\Dispatcher\CompositeParametersResolver;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;

$dispatcher = new MiddlewareDispatcher(
    new MiddlewareFactory(
        $diContainer, new CompositeParametersResolver(
            new CoolParametersResolver(),
            new YetAnotherParametersResolver(),
        )
    ),
    $eventDispatcher
);
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

The Yii Middleware Dispatcher is free software. It is released under the terms of the BSD License.
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
