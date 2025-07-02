<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii Message Translator</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/translator/v)](https://packagist.org/packages/yiisoft/translator)
[![Total Downloads](https://poser.pugx.org/yiisoft/translator/downloads)](https://packagist.org/packages/yiisoft/translator)
[![Build status](https://github.com/yiisoft/translator/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/translator/actions/workflows/build.yml)
[![Code Coverage](https://codecov.io/gh/yiisoft/translator/branch/master/graph/badge.svg)](https://codecov.io/gh/yiisoft/translator)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Ftranslator%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/translator/master)
[![static analysis](https://github.com/yiisoft/translator/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/translator/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/translator/coverage.svg)](https://shepherd.dev/github/yiisoft/translator)

This package allows translating messages into several languages. It can work with both Yii-based applications and
standalone PHP applications.

## Requirements

- PHP 8.0 or higher.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/translator
```

### Additional packages

There are two types of additional packages. Message source provide support of various message storage formats such as
PHP arrays or GNU gettext. Message formatters provide extra syntax that is recognized in translated messages.

#### Message sources

- [translator-message-php](https://github.com/yiisoft/translator-message-php) - PHP file message storage.
- [translator-message-db](https://github.com/yiisoft/translator-message-db) - Database message storage.
- [translator-message-gettext](https://github.com/yiisoft/translator-message-gettext) - gettext message storage.

## Built-in message formatters

- [Simple formatter](docs/guide/en/simple-formatter.md) just replaces parameters in messages. Does not take into account the 
locale.
- [`intl` formatter](docs/guide/en/intl-formatter.md) utilizes PHP intl extension message formatting capabilities.

## Extracting messages

The message extraction is done via [console extractor](https://github.com/yiisoft/translator-extractor) that searches
for translator message calls and builds translation files.

In some cases you need to do so without using console. If that is your case, check [extractor guide](docs/guide/en/extractor.md).

## Configuration

### Quick start

First, get a configured instance of event dispatcher. When using a framework it is usually done as:

```php
public function actionProcess(\Psr\EventDispatcher\EventDispatcherInterface $eventDispatcher)
{
    // ...
}
```

Configuration depends on the container used so below we'll create an instance manually.

```php
/** @var \Psr\EventDispatcher\EventDispatcherInterface $eventDispatcher */
$locale = 'ru';
$fallbackLocale = 'en';

$translator = new Yiisoft\Translator\Translator(
    $locale,
    $fallbackLocale,
    $eventDispatcher
);
```

`$fallbackLocale` and `$eventDispatcher` are optional. Fallback locale is used when no translation was found in the
main locale. Event dispatcher is used to dispatch missing translation events.

Now we've got an instance, but it has no idea where to get translations from. Let's tell it:

```php
// Default category is used when no category is specified explicitly.
$defaultCategoryName = 'app';
$pathToTranslations = './messages/';

// We use MessageSource that is based on PHP files.
$messageSource = new \Yiisoft\Translator\Message\Php\MessageSource($pathToTranslations);

// We use Intl message formatter.
$formatter = new \Yiisoft\Translator\IntlMessageFormatter(); 

// Now get an instance of CategorySource.
$category = new Yiisoft\Translator\CategorySource(
    $defaultCategoryName, 
    $messageSource,
    $formatter
);

// And add it.
$translator->addCategorySources($category);
```

That's it. Translator is ready to be used.

### Advanced configuration for Yii3 application

After installing the package, you will get the following configuration files in your application config:

- `config/packages/yiisoft/translator/common.php`
- `config/packages/yiisoft/translator/params.php`

You need get implementation of `MessageReader` and `MessageSource` to complete configuration. See
"Additional packages", "Message sources" above.

The following configuration is for Yii3 application after all needed packages installed:

You need uncomment strings around `ApplicationCategorySource` in `common.php` and `params.php` files:

```php
<?php
declare(strict_types=1);

use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Definitions\Reference;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Translator\Translator;
use Yiisoft\Translator\CategorySource;

/** @var array $params */

return [
    
    // Configure application CategorySource 
    ApplicationCategorySource::class => [ // <- Uncommented
        'class' => CategorySource::class,
        '__construct()' => [
            'name' => $params['yiisoft/translator']['defaultCategory'],
        ],
    ],
    
    TranslatorInterface::class => [
        'class' => Translator::class,
        '__construct()' => [
            $params['yiisoft/translator']['locale'],
            $params['yiisoft/translator']['fallbackLocale'],
            Reference::to(EventDispatcherInterface::class),
        ],
        'addCategorySources()' => [
            $params['yiisoft/translator']['categorySources']
        ],
    ],
];
```

and `params.php`:

```php
<?php

declare(strict_types=1);

use Yiisoft\Definitions\Reference;

return [
    'yiisoft/translator' => [
        'locale' => 'en-US',
        'fallbackLocale' => null,
        'defaultCategory' => 'app',
        'categorySources' => [
            // You can add categories to your application and your modules using `Reference::to` below
            Reference::to(ApplicationCategorySource::class), // <- Uncommented
            // Reference::to(MyModuleCategorySource::class),
        ],
    ],
];
```

### Multiple translation sources

```php
/** @var \Yiisoft\Translator\TranslatorInterface $translator */

$categoryName = 'module';
$pathToModuleTranslations = './module/messages/';
$moduleMessageSource = new \Yiisoft\Translator\Message\Php\MessageSource($pathToModuleTranslations);

// Simple message formatter.
$formatter = new \Yiisoft\Translator\Formatter\Simple\SimpleMessageFormatter();

$additionalCategory = new Yiisoft\Translator\CategorySource(
    $categoryName, 
    $moduleMessageSource,
    $formatter
);
$translator->addCategorySources($additionalCategory);
```

### Adding many category sources at once

```php
/** 
 * @var \Yiisoft\Translator\TranslatorInterface $translator
 * @var \Yiisoft\Translator\CategorySource $additionalCategory1
 * @var \Yiisoft\Translator\CategorySource $additionalCategory2 
 */

$translator->addCategorySources($additionalCategory1, $additionalCategory2);
```

### Overriding translation messages

If you use a module that has message translation and want to redefine default translation messages, you can
add your category source with the same `categoryName` as used in the module.

During translation `CategorySource`s are used from last to first allowing overriding messages of the same
category and ID.

```php
/** @var \Yiisoft\Translator\TranslatorInterface $translator */
/** @var \Yiisoft\Translator\Message\Php\MessageSource $yourCustomMessageSource */
/** @var \Yiisoft\Translator\Formatter\Simple\SimpleMessageFormatter $formatter */

// CategorySource for module with "validator" category name.
$categoryNameAsModule = 'validator'; // 
$moduleCategorySource = new Yiisoft\Translator\CategorySource(
    $categoryNameAsModule, 
    $yourCustomMessageSource,
    $formatter
);

// Needs be added after module category source is added.
$translator->addCategorySources($moduleCategorySource);
```

## General usage

### Using default language and default category

```php
// single translation
$messageIdentificator = 'submit';
echo $translator->translate($messageIdentificator);
// output: `Submit message`

// translation with plural
$messageIdentificator = 'multiHumans';
echo $translator->translate($messageIdentificator, ['n' => 3]);
// output: `3 humans`
```

### Specifying category and language

```php
$messageIdentificator = 'submit';
echo $translator->translate($messageIdentificator, [], 'moduleId', 'ru');
// output: `Отправить сообщение`
```

### Change default locale

```php
$newDefaultLocale = 'de-DE';
$translator->setLocale($newDefaultLocale);
```

### Get a current locale, if you don't know set locale

```php
echo $translator->getLocale();
```

### Get a new Translator instance with a locale to be used by default in case locale isn't specified explicitly

```php
$newDefaultLocale = 'de-DE';
echo $translator->withLocale($newDefaultLocale);
```

### Get a new Translator instance with a category to be used by default in case category isn't specified explicitly

```php
$newDefaultCategoryId = 'module2';
echo $translator->withDefaultCategory($newDefaultCategoryId);
```

## Additional info

The package contains interfaces for development of custom formatters, readers, and writers.

## Documentation

- Guide: [English](docs/guide/en/README.md), [Português - Brasil](docs/guide/pt-BR/README.md)
- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Message Translator is free software. It is released under the terms of the BSD License.
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
