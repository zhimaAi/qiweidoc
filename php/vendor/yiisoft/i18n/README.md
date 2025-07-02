<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii Internationalization Library</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/i18n/v/stable.png)](https://packagist.org/packages/yiisoft/i18n)
[![Total Downloads](https://poser.pugx.org/yiisoft/i18n/downloads.png)](https://packagist.org/packages/yiisoft/i18n)
[![Build status](https://github.com/yiisoft/i18n/workflows/build/badge.svg)](https://github.com/yiisoft/i18n/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/i18n/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/i18n/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/i18n/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/i18n/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fi18n%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/i18n/master)
[![static analysis](https://github.com/yiisoft/i18n/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/i18n/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/i18n/coverage.svg)](https://shepherd.dev/github/yiisoft/i18n)

The package provides common internationalization utilities:

- `Locale` stores locale information created from BCP 47 formatted string.
  It can parse locale string, modify locale parts,
  form locale string from parts, and derive fallback locale.
- `LocaleProvider` is a stateful service that stores current locale.

## Requirements

- PHP 7.4 or higher.

## Installation

The package could be installed with composer:

```shell
composer install yiisoft/i18n --prefer-dist
```

## General usage

Use `Locale` as follows:

```php
$locale = new \Yiisoft\I18n\Locale('es-CL');
echo $locale->language(); // es
echo $locale->region(); // CL

$locale = $locale->withLanguage('en');
echo $locale->asString(); // en-CL

echo $locale
    ->fallbackLocale()
    ->asString(); // en
```

Use `LocaleProvider` as follows:

```php
use \Yiisoft\I18n\LocaleProvider;

final class MyService
{
    public function __construct(
        private LocaleProvider $localeProvider
    ) {    
    }
    
    public function doIt(): void
    {
        $locale = $this->localeProvider->get();
        if ($this->localeProvider->isDefaultLocale()) {
            // ...
        }
        
        // ...        
    }
    
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

The Yii Internationalization Library is free software. It's released under the terms of the BSD License.
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
