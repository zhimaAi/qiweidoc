<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii Middleware</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/yii-middleware/v)](https://packagist.org/packages/yiisoft/yii-middleware)
[![Total Downloads](https://poser.pugx.org/yiisoft/yii-middleware/downloads)](https://packagist.org/packages/yiisoft/yii-middleware)
[![Build status](https://github.com/yiisoft/yii-middleware/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/yii-middleware/actions/workflows/build.yml)
[![Code Coverage](https://codecov.io/gh/yiisoft/yii-middleware/graph/badge.svg?token=fZ4S2L5kIJ)](https://codecov.io/gh/yiisoft/yii-middleware)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fyii-middleware%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/yii-middleware/master)
[![static analysis](https://github.com/yiisoft/yii-middleware/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/yii-middleware/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/yii-middleware/coverage.svg)](https://shepherd.dev/github/yiisoft/yii-middleware)
[![psalm-level](https://shepherd.dev/github/yiisoft/yii-middleware/level.svg)](https://shepherd.dev/github/yiisoft/yii-middleware)

The package provides middleware classes that implement [PSR-15](https://www.php-fig.org/psr/psr-15/#12-middleware):

- [`ForceSecureConnection`](#forcesecureconnection).
- [`HttpCache`](#httpcache).
- [`IpFilter`](#ipfilter).
- [`Redirect`](#redirect).
- [`Subfolder`](#subfolder).
- [`TagRequest`](#tagrequest).
- [`Locale`](#locale).
- [`CorsAllowAll`](#corsallowall).

For proxy related middleware, there is a separate package -
[Yii Proxy Middleware](https://github.com/yiisoft/proxy-middleware).

For more information on how to use middleware in the [Yii Framework](https://www.yiiframework.com/), see the
[Yii middleware guide](https://github.com/yiisoft/docs/blob/master/guide/en/structure/middleware.md).

## Requirements

- PHP 8.0 or higher.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/yii-middleware
```

## General usage

All classes are separate implementations of [PSR 15](https://github.com/php-fig/http-server-middleware)
middleware and don't interact with each other in any way.

### `ForceSecureConnection`

Redirects insecure requests from HTTP to HTTPS, and adds headers necessary to enhance the security policy.

```php
use Yiisoft\Yii\Middleware\ForceSecureConnection;

/**
 * @var Psr\Http\Message\ResponseFactoryInterface $responseFactory
 * @var Psr\Http\Message\ServerRequestInterface $request
 * @var Psr\Http\Server\RequestHandlerInterface $handler
 */

$middleware = new ForceSecureConnection($responseFactory);

// Enables redirection from HTTP to HTTPS:
$middleware = $middleware->withRedirection(301);
// Disables redirection from HTTP to HTTPS:
$middleware = $middleware->withoutRedirection();

$response = $middleware->process($request, $handler);
```

The `Content-Security-Policy` (CSP) header can force the browser to load page resources only through
a secure connection, even if links in the page layout are specified with an unprotected protocol.

```php
$middleware = $middleware->withCSP('upgrade-insecure-requests; default-src https:');
// Or without the `Content-Security-Policy` header in response:
$middleware = $middleware->withoutCSP();
```

Middleware adds HTTP Strict-Transport-Security (HSTS) header to each response.
The header tells the browser that your site works with HTTPS only.

```php
$maxAge = 3600; // Default is 31_536_000 (12 months).
$subDomains = false; // Whether to add the `includeSubDomains` option to the header value.

$middleware = $middleware->withHSTS($maxAge, $subDomains);
// Or without the `Strict-Transport-Security` header in response:
$middleware = $middleware->withoutHSTS();
```

### `HttpCache`

Implements client-side caching by utilizing the `Last-Modified` and `ETag` HTTP headers.

```php
use Yiisoft\Yii\Middleware\HttpCache;

/**
 * @var Psr\Http\Message\ServerRequestInterface $request
 * @var Psr\Http\Server\RequestHandlerInterface $handler
 */

$middleware = new HttpCache();

// Specify callable that generates the last modified:
$middleware = $middleware->withLastModified(function (ServerRequestInterface $request, mixed $params): int {
    $defaultLastModified = 3600;
    // Some actions.
    return $defaultLastModified;
});
// Specify callable that generates the ETag seed string:
$middleware = $middleware->withEtagSeed(function (ServerRequestInterface $request, mixed $params): string {
    $defaultEtagSeed = '33a64df551425fcc55e4d42a148795d9f25f89d4';
    // Some actions.
    return $defaultEtagSeed;
});

$response = $middleware->process($request, $handler);
```

Additionally, you can specify the following options:

```php
// Extra parameters for ETag seed string generation:
$middleware = $middleware->withParams(['parameter' => 'value']);

// The value of the `Cache-Control` HTTP header:
$middleware = $middleware->withCacheControlHeader('public, max-age=31536000');
// Default is `public, max-age=3600`. If null, the header won't be sent.

// Enable weak ETags generation (disabled by default):
$middleware = $middleware->withWeakTag();
// You should use weak ETags if the content is semantically equal, but not byte-equal.
```

### `IpFilter`

`IpFilter` allows access from specified IP ranges only and responds with 403 for all other IPs.

```php
use Yiisoft\Yii\Middleware\IpFilter;

/**
 * @var Psr\Http\Message\ResponseFactoryInterface $responseFactory
 * @var Psr\Http\Message\ServerRequestInterface $request
 * @var Psr\Http\Server\RequestHandlerInterface $handler
 * @var Yiisoft\Validator\Rule\ValidatorInterface $validator
 */

// Name of the request attribute holding client IP:
$clientIpAttribute = 'client-ip';
// If there is no such attribute, or it has no value, then the middleware will respond with 403 forbidden.
// If the name of the request attribute is `null`, then `REMOTE_ADDR` server parameter is used to determine client IP.

$middleware = new IpFilter($validator, $responseFactory, $clientIpAttribute);

// Change client IP validator:
$middleware = $middleware->withValidator($validator);

$response = $middleware->process($request, $handler);
```

### `Redirect`

Generates and adds a `Location` header to the response.

```php
use Yiisoft\Yii\Middleware\Redirect;

/**
 * @var Psr\Http\Message\ResponseFactoryInterface $responseFactory
 * @var Psr\Http\Message\ServerRequestInterface $request
 * @var Psr\Http\Server\RequestHandlerInterface $handler
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

$middleware = new Redirect($ipValidator, $urlGenerator);

// Specify URL for redirection:
$middleware = $middleware->toUrl('/login');
// Or specify route data for redirection:
$middleware = $middleware->toRoute('auth/login', ['parameter' => 'value']);
// If you have set a redirect URL with "toUrl()" method, the middleware ignores the route data, since the URL is a
// priority.

$response = $middleware->process($request, $handler);
```

You can also set the status of the response code for redirection.

```php
// For permanent redirection (301):
$middleware = $middleware->permanent();

// For temporary redirection (302):
$middleware = $middleware->permanent();

// Or specify the status code yourself:
$middleware = $middleware->withStatus(303);
```

### `Subfolder`

Supports routing when the entry point of the application isn't directly at the webroot.
By default, it determines webroot based on server parameters.

> Info: You should place this middleware before `Route` middleware in the middleware list.

If you want the application to run on the specified path, use the prefix instead:

```php
use Yiisoft\Yii\Middleware\Subfolder;

/**
 * @var Psr\Http\Message\ServerRequestInterface $request
 * @var Psr\Http\Server\RequestHandlerInterface $handler
 * @var Yiisoft\Aliases\Aliases $aliases
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */
 
// URI prefix the specified immediately after the domain part (default is `null`):
$prefix = '/blog';
// The prefix value usually begins with a slash and must not end with a slash.

$middleware = new Subfolder($urlGenerator, $aliases, $prefix);

$response = $middleware->process($request, $handler);
```

### `TagRequest`

Tags request with a random value that could be later used for identifying it.

```php
use Yiisoft\Yii\Middleware\TagRequest;

/**
 * @var Psr\Http\Message\ServerRequestInterface $request
 * @var Psr\Http\Server\RequestHandlerInterface $handler
 */

$middleware = new TagRequest();
// In the process, a request attribute with the name `requestTag`
// and the generated value by the function `uniqid()` will be added.
$response = $middleware->process($request, $handler);
```

### `Locale`

Supports locale-based routing and configures URL generator.

> Info: You should place this middleware before `Route` middleware in the middleware list.

```php
use Yiisoft\Yii\Middleware\Locale;

// Available locales.
$locales = ['en' => 'en-US', 'ru' => 'ru-RU', 'uz' => 'uz-UZ']
/**
 * Specify supported locales.
 * 
 * @var Locale $middleware
 */
$middleware = $middleware->withSupportedLocales($locales);

// Ignore requests which URLs that match "/api**" wildcard pattern.
$middleware = $middleware->withIgnoredRequestUrlPatterns(['/api**']);

$response = $middleware->process($request);
```

The priority of lookup is the following:

1. URI query path, that's `/de/blog`.
2. URI query parameter name, that's `/blog?_language=de`.
   You can customize parameter name via `withQueryParameterName()`.
3. Cookie named `_language`. You can customize name via `withCookieName()`.
4. `Accept-Language` header. Not enabled by default. Use `withDetectLocale(true)` to enable it.

Found locale is not saved by default. It can be saved to cookies:

```php
use Yiisoft\Yii\Middleware\Locale;

/** @var Locale $middleware */
$middleware = $middleware
    ->withCookieDuration(new DateInterval('P30D')) // Key parameter for activating saving to cookies.
    // Extra customization.
    ->withCookieName('_custom_name')
    ->withSecureCookie(true)
```

To configure more services, such as translator or session, use `SetLocaleEvent`
([Yii Event Dispatcher](https://github.com/yiisoft/event-dispatcher) is required).

```php
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\Middleware\Event\SetLocaleEvent;

final class SetLocaleEventHandler
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    public function handle(SetLocaleEvent $event): void
    {
        $this->translator->setLocale($event->getLocale());
    }
}
```

> Note: Using tranlator requires [Yii Message Translator](https://github.com/yiisoft/translator).

### `CorsAllowAll`

Adds CORS headers to the response.

## Documentation

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Middleware is free software. It is released under the terms of the BSD License.
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
