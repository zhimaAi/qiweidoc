<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii Data Response</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/data-response/v/stable.png)](https://packagist.org/packages/yiisoft/data-response)
[![Total Downloads](https://poser.pugx.org/yiisoft/data-response/downloads.png)](https://packagist.org/packages/yiisoft/data-response)
[![Build status](https://github.com/yiisoft/data-response/workflows/build/badge.svg)](https://github.com/yiisoft/data-response/actions?query=workflow%3Abuild)
[![Code Coverage](https://codecov.io/gh/yiisoft/data-response/graph/badge.svg?token=SPEX4FPFBU)](https://codecov.io/gh/yiisoft/data-response)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdata-response%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/data-response/master)
[![static analysis](https://github.com/yiisoft/data-response/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/data-response/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/data-response/coverage.svg)](https://shepherd.dev/github/yiisoft/data-response)

The package allows responding with data that is automatically converted into [PSR-7](https://www.php-fig.org/psr/psr-7/)
response.

## Requirements

- PHP 8.1 or higher.
- `DOM` PHP extension.

## Installation

The package could be installed via composer:

```shell
composer require yiisoft/data-response
```

## General usage

The package provides `DataResponseFactory` class that, given a [PSR-17](https://www.php-fig.org/psr/psr-17/)
response factory, is able to create data response.

Data response contains raw data to be processed later.

```php
use Yiisoft\DataResponse\DataResponseFactory;

/**
 * @var Psr\Http\Message\ResponseFactoryInterface $responseFactory
 */

$factory = new DataResponseFactory($responseFactory);
$dataResponse = $factory->createResponse('test');
$dataResponse
    ->getBody()
    ->rewind();

echo $dataResponse
    ->getBody()
    ->getContents(); // "test"
```

### Formatters

Formatter purpose is to format a data response. In the following example we format data as JSON.

```php
use Yiisoft\DataResponse\DataResponseFactory;
use Yiisoft\DataResponse\Formatter\JsonDataResponseFormatter;

/**
 * @var Psr\Http\Message\ResponseFactoryInterface $responseFactory
 */

$factory = new DataResponseFactory($responseFactory);
$dataResponse = $factory->createResponse('test');
$dataResponse = $dataResponse->withResponseFormatter(new JsonDataResponseFormatter());
$dataResponse
    ->getBody()
    ->rewind();

echo $dataResponse->getHeader('Content-Type'); // ["application/json; charset=UTF-8"]
echo $dataResponse
    ->getBody()
    ->getContents(); // "test"
```

The following formatters are available:

- `HtmlDataResponseFormatter`
- `JsonDataResponseFormatter`
- `XmlDataResponseFormatter`
- `PlainTextDataResponseFormatter`

### Middleware

The package provides a [PSR-15](https://www.php-fig.org/psr/psr-15/) middleware that is able to format a data response.

```php

use Yiisoft\DataResponse\Middleware\FormatDataResponse;
use Yiisoft\DataResponse\Formatter\JsonDataResponseFormatter;

$middleware = (new FormatDataResponse(new JsonDataResponseFormatter()));
//$middleware->process($request, $handler);
```

Also, the package provides [PSR-15](https://www.php-fig.org/psr/psr-15/) middleware for content negotiation:

```php
use Yiisoft\DataResponse\Formatter\HtmlDataResponseFormatter;
use Yiisoft\DataResponse\Formatter\XmlDataResponseFormatter;
use Yiisoft\DataResponse\Formatter\JsonDataResponseFormatter;
use Yiisoft\DataResponse\Middleware\ContentNegotiator;

$middleware = new ContentNegotiator([
    'text/html' => new HtmlDataResponseFormatter(),
    'application/xml' => new XmlDataResponseFormatter(),
    'application/json' => new JsonDataResponseFormatter(),
]);
```

You can override middlewares with method `withContentFormatters()`:

```php
$middleware->withContentFormatters([
    'application/xml' => new XmlDataResponseFormatter(),
    'application/json' => new JsonDataResponseFormatter(),
]);
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

The Data response is free software. It is released under the terms of the BSD License.
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
