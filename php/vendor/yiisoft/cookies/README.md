<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii Cookies</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/cookies/v)](https://packagist.org/packages/yiisoft/cookies)
[![Total Downloads](https://poser.pugx.org/yiisoft/cookies/downloads)](https://packagist.org/packages/yiisoft/cookies)
[![Build status](https://github.com/yiisoft/cookies/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/cookies/actions/workflows/build.yml)
[![Code Coverage](https://codecov.io/gh/yiisoft/cookies/graph/badge.svg?token=6FONX93IM5)](https://codecov.io/gh/yiisoft/cookies)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fcookies%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/cookies/master)
[![static analysis](https://github.com/yiisoft/cookies/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/cookies/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/cookies/coverage.svg)](https://shepherd.dev/github/yiisoft/cookies)

The package helps in working with HTTP cookies in a [PSR-7](https://www.php-fig.org/psr/psr-7/) environment:

- provides a handy abstraction representing a cookie
- allows dealing with many cookies at once
- forms and adds `Set-Cookie` headers to response
- signs a cookie to prevent its value from being tampered with
- encrypts a cookie to prevent its value from being tampered with
- provides [PSR-15](https://www.php-fig.org/psr/psr-15/) middleware for encrypting and signing cookie values

## Requirements

- PHP 7.4 or higher.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/cookies
```

## General usage

Adding a cookie to response:

```php
$cookie = (new \Yiisoft\Cookies\Cookie('cookieName', 'value'))
    ->withPath('/')
    ->withDomain('yiiframework.com')
    ->withHttpOnly(true)
    ->withSecure(true)
    ->withSameSite(\Yiisoft\Cookies\Cookie::SAME_SITE_STRICT)
    ->withMaxAge(new \DateInterval('P7D'));

$response = $cookie->addToResponse($response);
```

Modifying response cookies to be sent:

```php
$cookies = \Yiisoft\Cookies\CookieCollection::fromResponse($response);
$cookies->expire('login');
$response = $cookies->setToResponse($response);
```

Getting request cookies:

```php
$cookies = \Yiisoft\Cookies\CookieCollection::fromArray($request->getCookieParams());
```

Signing a cookie to prevent its value from being tampered with:

```php
$cookie = new \Yiisoft\Cookies\Cookie('identity', 'identityValue');

// The secret key used to sign and validate cookies.
$key = '0my1xVkjCJnD_q1yr6lUxcAdpDlTMwiU';
$signer = new \Yiisoft\Cookies\CookieSigner($key);

// Prefixes unique hash based on the value of the cookie and a secret key.
$signedCookie = $signer->sign($cookie);

// Validates and get backs the cookie with clean value.
$cookie = $signer->validate($signedCookie);

// Before validation, check if the cookie is signed.
if ($signer->isSigned($cookie)) {
    $cookie = $signer->validate($cookie);
}
```

Encrypting a cookie to prevent its value from being tampered with:

```php
$cookie = new \Yiisoft\Cookies\Cookie('identity', 'identityValue');

// The secret key used to sign and validate cookies.
$key = '0my1xVkjCJnD_q1yr6lUxcAdpDlTMwiU';
$encryptor = new \Yiisoft\Cookies\CookieEncryptor($key);

// Encrypts cookie value based on the secret key.
$encryptedCookie = $encryptor->encrypt($cookie);

// Validates, decrypts and get backs the cookie with clean value.
$cookie = $encryptor->decrypt($encryptedCookie);

// Before decryption, check if the cookie is encrypted.
if ($encryptor->isEncrypted($cookie)) {
    $cookie = $encryptor->decrypt($cookie);
}
```

Using a [PSR-15](https://www.php-fig.org/psr/psr-15/) middleware to encrypt and sign cookie values.

```php
/**
 * @var \Psr\Http\Message\ServerRequestInterface $request
 * @var \Psr\Http\Server\RequestHandlerInterface $handler
 * @var \Psr\Log\LoggerInterface $logger
 */

// The secret key used to sign and validate cookies.
$key = '0my1xVkjCJnD_q1yr6lUxcAdpDlTMwiU';
$signer = new \Yiisoft\Cookies\CookieSigner($key);
$encryptor = new \Yiisoft\Cookies\CookieEncryptor($key);

$cookiesSettings = [
    'identity' => \Yiisoft\Cookies\CookieMiddleware::ENCRYPT,
    'name_[1-9]' => \Yiisoft\Cookies\CookieMiddleware::SIGN,
    'prefix*' => \Yiisoft\Cookies\CookieMiddleware::SIGN,
];

$middleware = new \Yiisoft\Cookies\CookieMiddleware(
    $logger
    $encryptor,
    $signer,
    $cookiesSettings,
);

// The cookie parameter values from the request are decrypted/validated.
// The cookie values are encrypted/signed, and appended to the response.
$response = $middleware->process($request, $handler);
```

Create cookie with raw value that will not be encoded:

```php
$cookie = (new \Yiisoft\Cookies\Cookie('cookieName'))
    ->withRawValue('ebaKUq90PhiHck_MR7st-E1SxhbYWiTsLo82mCTbNuAh7rgflx5LVsYfJJseyQCrODuVcJkTSYhm1WKte-l5lQ==')
```

## Documentation

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Cookies is free software. It is released under the terms of the BSD License.
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
