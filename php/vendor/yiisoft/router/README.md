<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii Router</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/router/v/stable.png)](https://packagist.org/packages/yiisoft/router)
[![Total Downloads](https://poser.pugx.org/yiisoft/router/downloads.png)](https://packagist.org/packages/yiisoft/router)
[![Build status](https://github.com/yiisoft/router/workflows/build/badge.svg)](https://github.com/yiisoft/router/actions)
[![Code coverage](https://codecov.io/gh/yiisoft/router/graph/badge.svg?token=FxndVgUmF0)](https://codecov.io/gh/yiisoft/router)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Frouter%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/router/master)
[![static analysis](https://github.com/yiisoft/router/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/router/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/router/coverage.svg)](https://shepherd.dev/github/yiisoft/router)

The package provides [PSR-7](https://www.php-fig.org/psr/psr-7/) compatible request routing and
a [PSR-15 middleware](https://www.php-fig.org/psr/psr-15/) ready to be used in an application.
Instead of implementing routing from ground up, the package provides an interface for configuring routes and could be used
with an adapter package. Currently, the only adapter available is [FastRoute](https://github.com/yiisoft/router-fastroute).

## Features

- URL matching and URL generation supporting HTTP methods, hosts, and defaults.
- Good IDE support for defining routes.
- Route groups with infinite nesting. 
- Middleware support for both individual routes and groups.
- Ready to use middleware for route matching.
- Convenient `CurrentRoute` service that holds information about last matched route.
- Out of the box CORS middleware support.

## Requirements

- PHP 8.0 or higher.

## Installation

The package could be installed with composer:

```shell
composer require yiisoft/router
```

Additionally, you will need an adapter such as [FastRoute](https://github.com/yiisoft/router-fastroute).

## Defining routes and URL matching

Common usage of the router looks like the following:

```php
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Router\UrlMatcherInterface;
use Yiisoft\Router\Fastroute\UrlMatcher;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

// Define routes
$routes = [
    Route::get('/')
        ->action(static function (ServerRequestInterface $request, RequestHandlerInterface $next) use ($responseFactory) {
            $response = $responseFactory->createResponse();
            $response
                ->getBody()
                ->write('You are at homepage.');
            return $response;
        }),
    Route::get('/test/{id:\w+}')
        ->action(static function (CurrentRoute $currentRoute, RequestHandlerInterface $next) use ($responseFactory) {
            $id = $currentRoute->getArgument('id');
    
            $response = $responseFactory->createResponse();
            $response
                ->getBody()
                ->write('You are at test with argument ' . $id);
            return $response;
        })
];

// Add routes defined to route collector
$collector = $container->get(RouteCollectorInterface::class);
$collector->addGroup(Group::create(null)->routes($routes));

// Initialize URL matcher
/** @var UrlMatcherInterface $urlMatcher */
$urlMatcher = new UrlMatcher(new RouteCollection($collector));

// Do the match against $request which is PSR-7 ServerRequestInterface. 
$result = $urlMatcher->match($request);

if (!$result->isSuccess()) {
     // 404
}

// $result->arguments() contains arguments from the match.

// Run middleware assigned to a route found.
$response = $result->process($request, $notFoundHandler);
```

> Note: Despite `UrlGeneratorInterface` and `UrlMatcherInterface` being common for all adapters available, certain
> features and, especially, pattern syntax may differ. To check usage and configuration details, please refer
> to specific adapter documentation. All examples in this document are for
> [FastRoute adapter](https://github.com/yiisoft/router-fastroute).
 
### Middleware usage

In order to simplify usage in PSR-middleware based application, there is a ready to use middleware provided:

```php
$router = $container->get(Yiisoft\Router\UrlMatcherInterface::class);
$responseFactory = $container->get(\Psr\Http\Message\ResponseFactoryInterface::class);

$routerMiddleware = new Yiisoft\Router\Middleware\Router($router, $responseFactory, $container);

// Add middleware to your middleware handler of choice.
```

In case of a route match router middleware executes handler middleware attached to the route. If there is no match, next
application middleware processes the request.

### Routes

Route could match for one or more HTTP methods: `GET`, `POST`, `PUT`, `DELETE`, `PATCH`, `HEAD`, `OPTIONS`. There are
corresponding static methods for creating a route for a certain method. If a route is to handle multiple methods at once,
it could be created using `methods()`.

```php
use Yiisoft\Router\Route;

Route::delete('/post/{id}')
    ->name('post-delete')
    ->action([PostController::class, 'actionDelete']);
    
Route::methods([Method::GET, Method::POST], '/page/add')
    ->name('page-add')
    ->action([PageController::class, 'actionAdd']);
```

If you want to generate a URL based on route and its parameters, give it a name with `name()`. Check "Creating URLs"
for details.

`action()` in the above is a primary middleware definition that is invoked last when matching result `process()`
method is called. How middleware are executed and what middleware formats are accepted is defined by middleware
dispatcher used. See [readme of yiisoft/middleware-dispatcher](https://github.com/yiisoft/middleware-dispatcher)
for middleware examples.  

If a route should be applied only to a certain host, it could be defined like the following:

```php
use Yiisoft\Router\Route;

Route::get('/special')
    ->name('special')
    ->action(SpecialAction::class)
    ->host('https://www.yiiframework.com');
```

Defaults for parameters could be provided via `defaults()` method:

```php
use Yiisoft\Router\Route;

Route::get('/api[/v{version}]')
    ->name('api-index')
    ->action(ApiAction::class)
    ->defaults(['version' => 1]);
```

In the above we specify that if "version" is not obtained from URL during matching then it will be `1`.

Besides action, additional middleware to execute before the action itself could be defined:

```php
use Yiisoft\Router\Route;

Route::methods([Method::GET, Method::POST], '/page/add')
    ->middleware(Authentication::class)
    ->middleware(ExtraHeaders::class)
    ->action([PostController::class, 'add'])
    ->name('blog/add');
```

It is typically used for a certain actions that could be reused for multiple routes such as authentication.

If there is a need to either add middleware to be executed first or remove existing middleware from a route,
`prependMiddleware()` and `disableMiddleware()` could be used. 

If you combine routes from multiple sources and want last route to have priority over existing ones, mark it as "override":

```php
use Yiisoft\Router\Route;

Route::get('/special')
    ->name('special')
    ->action(SpecialAction::class)
    ->override();
```

### Route groups

Routes could be grouped. That is useful for API endpoints and similar cases:

```php
use \Yiisoft\Router\Route;
use \Yiisoft\Router\Group;
use \Yiisoft\Router\RouteCollectorInterface;

// for obtaining router see adapter package of choice readme
$collector = $container->get(RouteCollectorInterface::class);
    
$collector->addGroup(
    Group::create('/api')
        ->middleware(ApiAuthentication::class)
        ->host('https://example.com')
        ->routes([
            Route::get('/comments'),
            Group::create('/posts')->routes([
                Route::get('/list'),
            ]),
        ])
);
```

A group could have a prefix, such as `/api` in the above. The prefix is applied for each group's route both when
matching and when generating URLs.

Similar to individual routes, a group could have a set of middleware managed using `middleware()`, `prependMiddleware()`,
and `disableMiddleware()`. These middleware are executed prior to matched route's own middleware and action.

If host is specified, all routes in the group would match only if the host match.

### Automatic OPTIONS response and CORS

By default, router responds automatically to OPTIONS requests based on the routes defined:

```
HTTP/1.1 204 No Content
Allow: GET, HEAD
```

Generally that is fine unless you need [CORS headers](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS). In this
case, you can add a middleware for handling it such as [tuupola/cors-middleware](https://github.com/tuupola/cors-middleware):

```php
use Yiisoft\Router\Group;
use \Tuupola\Middleware\CorsMiddleware;

return [
    Group::create('/api')
        ->withCors(CorsMiddleware::class)
        ->routes([
          // ...
        ]
    );
];
```

## Creating URLs

URLs could be created using `UrlGeneratorInterface::generate()`. Let's assume a route is defined like the following:

```php
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Yiisoft\Yii\Http\Handler\NotFoundHandler;
use Yiisoft\Yii\Runner\Http\SapiEmitter;
use Yiisoft\Yii\Runner\Http\ServerRequestFactory;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Router\Fastroute\UrlMatcher;


$request = $container
    ->get(ServerRequestFactory::class)
    ->createFromGlobals();
$responseFactory = $container->get(ResponseFactoryInterface::class);
$notFoundHandler = new NotFoundHandler($responseFactory);
$collector = $container->get(RouteCollectorInterface::class);
$collector->addRoute(
    Route::get('/test/{id:\w+}')
        ->action(static function (CurrentRoute $currentRoute, RequestHandlerInterface $next) use ($responseFactory) {
            $id = $currentRoute->getArgument('id');
            $response = $responseFactory->createResponse();
            $response
                ->getBody()
                ->write('You are at test with argument ' . $id);

           return $response;
        })
        ->name('test')
);
$router = new UrlMatcher(new RouteCollection($collector));
$route = $router->match($request);
$response = $route->process($request, $notFoundHandler);
$emitter = new SapiEmitter();
$emitter->emit($response, $request->getMethod() === Method::HEAD);
```

Then that is how URL could be obtained for it:

```php
use Yiisoft\Router\UrlGeneratorInterface;

function getUrl(UrlGeneratorInterface $urlGenerator, $parameters = [])
{
    return $urlGenerator->generate('test', $parameters);
}
```

Absolute URL cold be generated using `UrlGeneratorInterface::generateAbsolute()`:

```php
use Yiisoft\Router\UrlGeneratorInterface;

function getUrl(UrlGeneratorInterface $urlGenerator, $parameters = [])
{
    return $urlGenerator->generateAbsolute('test', $parameters);
}
```

Also, there is a handy `UrlGeneratorInterface::generateFromCurrent()` method. It allows generating a URL that is
a modified version of the current URL:

```php
use Yiisoft\Router\UrlGeneratorInterface;

function getUrl(UrlGeneratorInterface $urlGenerator, $id)
{
    return $urlGenerator->generateFromCurrent(['id' => 42]);
}
```

In the above, ID will be replaced with 42 and the rest of the parameters will stay the same. That is useful for
modifying URLs for filtering and/or sorting.

## Obtaining current route information

For such a route:

```php
use \Yiisoft\Router\Route;

$routes = [
    Route::post('/post/{id:\d+}')
        ->action([PostController::class, 'actionEdit']),
];
```

The information could be obtained as follows:

```php
use Psr\Http\Message\ResponseInterface
use Psr\Http\Message\UriInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\Route;

final class PostController
{   
    public function actionEdit(CurrentRoute $currentRoute): ResponseInterface
    {
        $postId = $currentRoute->getArgument('id');
        if ($postId === null) {
            throw new \InvalidArgumentException('Post ID is not specified.');
        }
        
        // ...
    
    }
}
```

In addition to commonly used `getArgument()` method, the following methods are available:

- `getArguments()` - To obtain all arguments at once. 
- `getName()` - To get route name.
- `getHost()` - To get route host.
- `getPattern()` - To get route pattern.
- `getMethods()` - To get route methods.
- `getUri()` - To get current URI.

## Testing

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```shell
./vendor/bin/infection
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

## License

The Yii Dependency Injection is free software. It is released under the terms of the BSD License.
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
