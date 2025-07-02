<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii Session</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/session/v/stable.png)](https://packagist.org/packages/yiisoft/session)
[![Total Downloads](https://poser.pugx.org/yiisoft/session/downloads.png)](https://packagist.org/packages/yiisoft/session)
[![Build status](https://github.com/yiisoft/session/workflows/build/badge.svg)](https://github.com/yiisoft/session/actions?query=workflow%3Abuild)
[![Code Coverage](https://codecov.io/gh/yiisoft/session/graph/badge.svg?token=CAD0XO9JZM)](https://codecov.io/gh/yiisoft/session)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fsession%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/session/master)
[![static analysis](https://github.com/yiisoft/session/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/session/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/session/coverage.svg)](https://shepherd.dev/github/yiisoft/session)

The package implements a session service, [PSR-15](https://www.php-fig.org/psr/psr-15/) session middleware,
and a flash message service which helps use one-time messages.

## Requirements

- PHP 8.0 or higher.

## Installation

The package could be installed with composer:

```shell
composer require yiisoft/session
```

In order to maintain a session between requests you need to add `SessionMiddleware` to your route group or
application middlewares. Route group should be preferred when you have both API with token-based authentication
and regular web routes in the same application. Having it this way avoids starting the session for API endpoints.

### Yii 3 configuration

In order to add a session for a certain group of routes, edit `config/routes.php` like the following:

```php
<?php

declare(strict_types=1);

use Yiisoft\Router\Group;
use Yiisoft\Session\SessionMiddleware;

return [
    Group::create('/blog')
        ->middleware(SessionMiddleware::class)
        ->routes(
            // ...
        )
];
```

To add a session to the whole application, edit `config/application.php` like the following:

```php
return [
    Yiisoft\Yii\Http\Application::class => [
        '__construct()' => [
            'dispatcher' => DynamicReference::to(static function (Injector $injector) {
                return ($injector->make(MiddlewareDispatcher::class))
                    ->withMiddlewares(
                        [
                            ErrorCatcher::class,
                            SessionMiddleware::class, // <-- add this
                            CsrfMiddleware::class,
                            Router::class,
                        ]
                    );
            }),
        ],
    ],
];
```

## General usage

You can access session data through `SessionInterface`.

```php
public function actionProfile(\Yiisoft\Session\SessionInterface $session)
{
    // get a value
    $lastAccessTime = $session->get('lastAccessTime');

    // get all values
    $sessionData = $session->all();
        
    // set a value
    $session->set('lastAccessTime', time());

    // check if value exists
    if ($session->has('lastAccessTime')) {
        // ...    
    }
    
    // remove value
    $session->remove('lastAccessTime');

    // get value and then remove it
    $sessionData = $session->pull('lastAccessTime');

    // clear session data from runtime
    $session->clear();
}
```

In case you need some data to remain in session until read, such as in case with displaying a message on the next page
flash messages is what you need. A flash message is a special type of data, that is available only in the current request
and the next request. After that, it will be deleted automatically.

`FlashInteface` usage is the following:

```php
/** @var Yiisoft\Session\Flash\FlashInterface $flash */

// request 1
$flash->set('warning', 'Oh no, not again.');

// request 2
$warning = $flash->get('warning');
if ($warning !== null) {
    // do something with it
}
```

## Opening and closing session

```php
public function actionProfile(\Yiisoft\Session\SessionInterface $session)
{
    // start session if it's not yet started
    $session->open();

    // work with session

    // write session values and then close it
    $session->close();
}
``` 

> Note: Closing session as early as possible is a good practice since many session implementations are blocking other
> requests while session is open.

There are two more ways to close session:

```php
public function actionProfile(\Yiisoft\Session\SessionInterface $session)
{
    // discard changes and close session
    $session->discard();

    // destroy session completely
    $session->destroy();    
}
```

## Custom session storage

When using `Yiisoft\Session\Session` as session component, you can provide your own storage implementation:

```php
$handler = new MySessionHandler();
$session = new \Yiisoft\Session\Session([], $handler);
```

Custom storage must implement `\SessionHandlerInterface`.

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

The Yii Session is free software. It is released under the terms of the BSD License.
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
