<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii Console Runner</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/yii-runner-console/v/stable.png)](https://packagist.org/packages/yiisoft/yii-runner-console)
[![Total Downloads](https://poser.pugx.org/yiisoft/yii-runner-console/downloads.png)](https://packagist.org/packages/yiisoft/yii-runner-console)
[![Build status](https://github.com/yiisoft/yii-runner-console/workflows/build/badge.svg)](https://github.com/yiisoft/yii-runner-console/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/yii-runner-console/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/yii-runner-console/?branch=master)
[![static analysis](https://github.com/yiisoft/yii-runner-console/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/yii-runner-console/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/yii-runner-console/coverage.svg)](https://shepherd.dev/github/yiisoft/yii-runner-console)

The package contains a bootstrap for running Yii3 console application.

## Requirements

- PHP 8.0 or higher.

## Installation

The package could be installed with composer:

```shell
composer require yiisoft/yii-runner-console
```

## General usage

In your console entry script do the following:

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

use Yiisoft\Yii\Runner\Console\ConsoleApplicationRunner;

require_once __DIR__ . '/autoload.php';

(new ConsoleApplicationRunner(
    rootPath: __DIR__,
    debug: $_ENV['YII_DEBUG'], 
    checkEvents: $_ENV['YII_DEBUG'], 
    environment: $_ENV['YII_ENV']
))->run();
```

### Additional configuration

By default, the `ConsoleApplicationRunner` is configured to work with Yii application templates and follows the
[config groups convention](https://github.com/yiisoft/docs/blob/master/022-config-groups.md).

You can override the default configuration using constructor parameters and immutable setters.

#### Constructor parameters

`$rootPath` — the absolute path to the project root.

`$debug` — whether the debug mode is enabled.

`$checkEvents` — whether check events' configuration.

`$environment` — the environment name.

`$bootstrapGroup` — the bootstrap configuration group name.

`$eventsGroup` — the events' configuration group name.

`$diGroup` — the container definitions' configuration group name.

`$diProvidersGroup` — the container providers' configuration group name.

`$diDelegatesGroup` — the container delegates' configuration group name.

`$diTagsGroup` — the container tags' configuration group name.

`$paramsGroup` — the config parameters group name.

`$nestedParamsGroups` — configuration group names that are included into config parameters group. This is needed for
recursive merge parameters.

`$nestedEventsGroups` — configuration group names that are included into events' configuration group. This is needed for
reverse and recursive merge events' configurations.

#### Immutable setters

If the configuration instance settings differ from the default you can specify a customized configuration instance:

```php
/**
 * @var Yiisoft\Config\ConfigInterface $config
 * @var Yiisoft\Yii\Runner\Console\ConsoleApplicationRunner $runner
 */

$runner = $runner->withConfig($config);
```

The default container is `Yiisoft\Di\Container`. But you can specify any implementation
of the `Psr\Container\ContainerInterface`:

```php
/**
 * @var Psr\Container\ContainerInterface $container
 * @var Yiisoft\Yii\Runner\Console\ConsoleApplicationRunner $runner
 */

$runner = $runner->withContainer($container);
```

## Testing

The package is tested with [Codeception](https://codeception.com/). To run tests:

```shell
./vendor/bin/codecept run
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

## License

The Yii Console Runner is free software. It is released under the terms of the BSD License.
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
