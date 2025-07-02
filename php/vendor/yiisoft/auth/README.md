<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii Auth</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/auth/v/stable.png)](https://packagist.org/packages/yiisoft/auth)
[![Total Downloads](https://poser.pugx.org/yiisoft/auth/downloads.png)](https://packagist.org/packages/yiisoft/auth)
[![Build status](https://github.com/yiisoft/auth/workflows/build/badge.svg)](https://github.com/yiisoft/auth/actions)
[![Code Coverage](https://codecov.io/gh/yiisoft/auth/graph/badge.svg?token=HEZPNOVX64)](https://codecov.io/gh/yiisoft/auth)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fauth%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/auth/master)
[![static analysis](https://github.com/yiisoft/auth/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/auth/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/auth/coverage.svg)](https://shepherd.dev/github/yiisoft/auth)

The package provides various authentication methods, a set of abstractions to implement in your application, and
a [PSR-15](https://www.php-fig.org/psr/psr-15/) middleware to authenticate an identity.

## Requirements

- PHP 8.0 or higher.

## Installation

```shell
composer require yiisoft/auth
```

## General usage

Configure a middleware and add it to your middleware stack:

```php
$identityRepository = getIdentityWithTokenRepository(); // \Yiisoft\Auth\IdentityRepositoryInterface
$authenticationMethod = new \Yiisoft\Auth\Method\HttpBasic($identityRepository);

$middleware = new \Yiisoft\Auth\Middleware\Authentication(
    $authenticationMethod,
    $responseFactory, // PSR-17 ResponseFactoryInterface
    $failureHandler // optional, \Yiisoft\Auth\Handler\AuthenticationFailureHandler by default
);

$middlewareDispatcher->addMiddleware($middleware);
```

In order to get an identity instance in the following middleware use `getAttribute()` method of the request instance:

```php
public function actionIndex(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
{
    $identity = $request->getAttribute(\Yiisoft\Auth\Middleware\Authentication::class);
    // ...
}
```

### HTTP basic authentication

Basic HTTP authentication is typically used for entering login and password in the browser.
Credentials are passed as `$_SERVER['PHP_AUTH_USER']` and `$_SERVER['PHP_AUTH_PW']`.

```php
$authenticationMethod = (new \Yiisoft\Auth\Method\HttpBasic($identityRepository))
    ->withRealm('Admin')
    ->withAuthenticationCallback(static function (
        ?string $username,
        ?string $password,
        \Yiisoft\Auth\IdentityWithTokenRepositoryInterface $identityRepository
    ): ?\Yiisoft\Auth\IdentityInterface {
        return $identityRepository->findIdentityByToken($username, \Yiisoft\Auth\Method\HttpBasic::class);
    });
```

Realm is typically what you will see in the browser prompt asking for a login and a password.
Custom authentication callback set in the above is the same as default behavior when it is not specified.

### HTTP bearer authentication

Bearer HTTP authentication is typically used in APIs. Authentication token is passed in `WWW-Authenticate` header.

```php
$authenticationMethod = new \Yiisoft\Auth\Method\HttpBearer($identityRepository);
```

### Custom HTTP header authentication

Custom HTTP header could be used if you do not want to leverage bearer token authentication:

```php
 $authenticationMethod = (new \Yiisoft\Auth\Method\HttpHeader($identityRepository))
     ->withHeaderName('X-Api-Key')
     ->withPattern('/(.*)/'); // default
```

In the above we use full value of `X-Api-Key` header as token.

### Query parameter authentication

This authentication method is mainly used by clients unable to send headers. In case you do not have such clients
we advise not to use it.

```php
$authenticationMethod = (new \Yiisoft\Auth\Method\QueryParameter($identityRepository))
    ->withParameterName('token');
```

### Using multiple authentication methods

To use multiple authentication methods, use `Yiisoft\Auth\Method\Composite`:

```php
$authenticationMethod = new \Yiisoft\Auth\Method\Composite([
    $bearerAuthenticationMethod,
    $basicAuthenticationMethod
]);
```

## Extension and integration points

- `\Yiisoft\Auth\IdentityInterface` should be implemented by your application identity class. Typically, that is `User`.
- `\Yiisoft\Auth\IdentityRepositoryInterface` should be implemented by your application identity repository class.
  Typically, that is `UserIdentity`.
- `\Yiisoft\Auth\IdentityWithTokenRepositoryInterface` could be additionally implemented by your application
  identity repository class in case token-based authentication is needed. Typically, that is `UserIdentity`.
- `\Yiisoft\Auth\AuthenticationMethodInterface` could be implemented to provide your own authentication method.

## Documentation

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Auth is free software. It is released under the terms of the BSD License.
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
