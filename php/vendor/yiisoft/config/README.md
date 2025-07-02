<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
        <img src="docs/logo.png" height="100px" alt="Config">
    </a>
    <h1 align="center">Yii Config</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/config/v/stable)](https://packagist.org/packages/yiisoft/config)
[![Total Downloads](https://poser.pugx.org/yiisoft/config/downloads)](https://packagist.org/packages/yiisoft/config)
[![Build status](https://github.com/yiisoft/config/workflows/build/badge.svg)](https://github.com/yiisoft/config/actions)
[![Code Coverage](https://codecov.io/gh/yiisoft/config/graph/badge.svg?token=V8gfhkSUoP)](https://codecov.io/gh/yiisoft/config)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fconfig%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/config/master)
[![static analysis](https://github.com/yiisoft/config/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/config/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/config/coverage.svg)](https://shepherd.dev/github/yiisoft/config)

This [Composer](https://getcomposer.org) plugin provides assembling of configurations distributed with composer
packages. It is implementing a plugin system which allows to provide the configuration needed to use a package directly when installing it to make it run out-of-the-box.
The package becomes a plugin holding both the code and its default configuration.

## Requirements

- PHP 8.1 or higher.
- Composer 2.3 or higher.

## Installation

```shell
composer require yiisoft/config
```

## How it works?

The package consist of two parts: Composer plugin and config loader.

After composer updates its autoload file, and that happens after `dump-autoload`, `require`, `update` or `remove`,
Composer plugin:

- Scans installed packages for `config-plugin` extra option in their `composer.json`.
- Writes a merge plan into `config/.merge-plan.php`. It includes configuration from each package `composer.json`.
  
In the application entry point, usually `index.php`, we create an instance of config loader and require a configuration
we need:

```php
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigPaths;

$config = new Config(
    new ConfigPaths(dirname(__DIR__)),
);

$web = $config->get('web');
```

The `web` in the above is a config group. The config loader obtains it runtime according to the merge plan.
The configuration consists of three layers that are loaded as follows:

- Vendor configurations from each `vendor/package-name`. These provide default values.
- Root package configurations from `config`. These may override vendor configurations.
- Environment specific configurations from `config`. These may override root and vendor configurations.

> Please note that same named keys are not allowed within a configuration layer.

When calling the `get()` method, if the configuration group does not exist, an `\ErrorException` will be thrown.
If you are not sure that the configuration group exists, then use the `has()` method:

```php
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigPaths;

$config = new Config(
    new ConfigPaths(dirname(__DIR__)),
);

if ($config->has('web')) {
    $web = $config->get('web');
}
```

## Config groups

Each config group represents a set of configs that is merged into a single array. It is defined per package in
each package `composer.json`:

```json
"extra": {
    "config-plugin": {
        "params": [
            "params.php",
            "?params-local.php"
        ],
        "common": "common.php",
        "web": [
            "$common",
            "web.php",
            "../src/Modules/*/config/web.php"
        ],
        "other": "other.php"
    }
}
```

In the above example the mapping keys are config group names and the values are configuration files and references to other config groups.
The file paths are relative to the [source-directory](#source-directory), which by default is the path where `composer.json` is located.

### Markers

- `?` - marks optional files. Absence of files not marked with this marker will cause exception.

    ```php
    "params": [
       "params.php",
       "?params-local.php"
    ]
    ```

  It's okay if `params-local.php` will not be found, but it's not okay if `params.php` will be absent.
  
- `*` - marks wildcard path. It means zero or more matches by wildcard mask.

  ```php
  "web": [
     "../src/Modules/*/config/web.php"
  ]
  ```

  It will collect all `web.php` in any sub-folders of `src/Modules/` in `config` folder.
  However, if the configuration folder is packaged as part of the `PHAR` archive, the configuration
  files will not be uploaded. In this case, you must explicitly specify each configuration file.

- `$` - reference to another config by its group name.

  ```php
  "params": [
     "params.php",
     "?params-local.php"
  ],
  "params-console": [
     "$params",
     "params-console.php"
  ],
  "params-web": [
     "$params",
     "params-web.php"
  ]
  ```

  The config groups `params-console` and `params-web` will both contain the config values from `params.php` and `params-local.php` additional to their own configuration values.

***

Define your configs like the following:

```php
return [
    'components' => [
        'db' => [
            'class' => \my\Db::class,
            'name' => $params['db.name'],
            'password' => $params['db.password'],
        ],
    ],
];
```

A special variable `$params` is read from config (by default, group is named `params`).

### Using custom group for `$params`

By default, `$params` variable is read from `params` group. You can customize the group name via constructor of `Config`:

```php
$config = new Config(
    new ConfigPaths(__DIR__ . '/configs'),
    null,
    [],
    'custom-params' // Group name for `$params`
);
```

You can pass `null` as `$params` group name. In this case `$params` will empty array.

### Using sub-configs

In order to access a sub-config, use the following in your config:

```php
'routes' => $config->get('routes');
```

## Options

A number of options is available both for Composer plugin and a config loader. Composer options are specified in
`composer.json`:

```json
"extra": {
    "config-plugin-options": {
      "source-directory": "config"
    },
    "config-plugin": {
        // ...
    }
}
```

### `source-directory`

The `source-directory` option specifies where to read the configs from for a package the option is specified for.
It is available for all packages, including the root package, which is typically an application.
The value is a path relative to where the `composer.json` file is located. The default value is an empty string.

If you change the source directory for the root package, don't forget to adjust configs path when creating
an instance of `Config`. Usually that is `index.php`:

```php
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigPaths;

$config = new Config(
    new ConfigPaths(dirname(__DIR__), 'path/to/config/directory'),
);

$web = $config->get('web');
```

### `vendor-override-layer`

The `vendor-override-layer` option adds a sublayer to the vendor, which allocates packages that will override
the vendor's default configurations. This sublayer is located between the vendor and application layers.

This can be useful if you need to redefine default configurations even before the application layer. To do this,
you need to create your own package with configurations meant to override the default ones:

```json
"name": "vendor-name/package-name",
"extra": {
    "config-plugin": {
        // ...
    }
}
```

And in the root file `composer.json` of your application, specify this package in the `vendor-override-layer` option:

```json
"require": {
    "vendor-name/package-name": "version",
    "yiisoft/config": "version"
},
"extra": {
    "config-plugin-options": {
        "vendor-override-layer": "vendor-name/package-name"
    },
    "config-plugin": {
        // ...
    }
}
```

In the same way, several packages can be added to this sublayer:

```json
"extra": {
    "config-plugin-options": {
        "vendor-override-layer": [
            "vendor-name/package-1",
            "vendor-name/package-2"
        ]
    }
}
```

You can use wildcard pattern if there are too many packages:

```json
"extra": {
    "config-plugin-options": {
        "vendor-override-layer": [
            "vendor-1/*",
            "vendor-2/config-*"
        ]
    }
}
```

For more information about the wildcard syntax, see the [yiisoft/strings](https://github.com/yiisoft/strings).

> Please note that in this sublayer keys with the same names are not allowed similar to other layers.

### `merge-plan-file`

This option allows you to override path to merge plan file. It is `.merge-plan.php` by default. To change it, set the value:

```json
"extra": {
    "config-plugin-options": {
        "merge-plan-file": "custom/path/my-merge-plan.php"
    }
}
```

This can be useful when developing. Don't forget to set same path in `Config` constructor when changing this option.

### `build-merge-plan`

The `build-merge-plan` option allows you to disable creation/updating of the `config/.merge-plan.php`.
Enabled by default, to disable it, set the value to `false`:

```json
"extra": {
    "config-plugin-options": {
        "build-merge-plan": false
    }
}
```

This can be useful when developing. If the config package is a dependency of your package,
and you do not need to create a merge plan file when developing your package.
For example, this is implemented in [yiisoft/yii-runner](https://github.com/yiisoft/yii-runner).

### `package-types`

The `package-types` option define package types for process by composer plugin. By default, it is "library" and
"composer-plugin". You can override default value by own types:

```json
"extra": {
    "config-plugin-options": {
        "package-types": ["library", "my-extension"]
    }
}
```

## Environments

The plugin supports creating additional environments added to the base configuration. This allows you to create
multiple configurations for the application such as `production` and `development`.

> Note that environments are supported on application level only and are not read from configurations of packages.

The environments are specified in the `composer.json` file of your application:

```json
"extra": {
    "config-plugin-options": {
        "source-directory": "config"
    },
    "config-plugin": {
        "params": "params.php",
        "web": "web.php"
    },
    "config-plugin-environments": {
        "dev": {
            "params": "dev/params.php",
            "app": [
                "$web",
                "dev/app.php"
            ]
        },
        "prod": {
            "app": "prod/app.php"
        }
    }
}
```

Configuration defines the merge process. One of the environments from `config-plugin-environments`
is merged with the main configuration defined by `config-plugin`. In given example, in the `dev` environment
we use `$web` configuration from the main environment.

This configuration has the following structure:

```
config/             Configuration root directory.
    dev/            Development environment files.
        app.php     Development environment app group configuration.
        params.php  Development environment parameters.
    prod/           Production environment files.
        app.php     Production environment app group configuration.
    params.php      Main configuration parameters.
    web.php         Мain configuration web group configuration.
```

To choose an environment to be used you must specify its name when creating an instance of `Config`:

```php
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigPaths;

$config = new Config(
    new ConfigPaths(dirname(__DIR__)),
    'dev',
);

$app = $config->get('app');
```

If defined in an environment, `params` will be merged with `params` from the main configuration,
and could be used as `$params` in all configurations.

## Configuration in a PHP file

You can define configuration in a PHP file. To do it, specify a PHP file path in the `extra` section of
the `composer.json`:

```json
"extra": {
    "config-plugin-file": "path/to/configuration/file.php"
}
```

Configurations are specified in the same way, only in PHP format:

```php
return [
    'config-plugin-options' => [
        'source-directory' => 'config',  
    ],
    'config-plugin' => [
        'params' => [
            'params.php',
            '?params-local.php',
        ],
        'web' => 'web.php', 
    ],
    'config-plugin-environments' => [
        'dev' => [
            'params' => 'dev/params.php',
            'app' => [
                '$web',
                'dev/app.php',
            ],
        ],
        'prod' => [
            'app' => 'prod/app.php',
        ],
    ],
];
```

If you specify the file path, the remaining sections (`config-plugin-*`) in `composer.json` will be ignored and
configurations will be read from the PHP file specified. The path is relative to where the `composer.json` file
is located.

## Configuration modifiers

### Recursive merge of arrays

By default, recursive merging of arrays in configuration files is not performed. If you want to recursively merge
arrays in a certain group of configs, such as params, you must pass `RecursiveMerge` modifier with specified
group names to the `Config` constructor:

```php
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Config\Modifier\RecursiveMerge;

$config = new Config(
    new ConfigPaths(dirname(__DIR__)),
    'dev',
    [
        RecursiveMerge::groups('params', 'events', 'events-web', 'events-console'),
    ],
);

$params = $config->get('params'); // merged recursively
```

If you want to recursively merge arrays to a certain depth, use the `RecursiveMerge::groupsWithDepth()` method:

```php
RecursiveMerge::groups(['widgets-themes', 'my-custom-group'], 1)
```

> Note: References to another configs use recursive modifier of root group.

### Reverse merge of arrays

Result of reverse merge is being ordered descending by data source. It is useful for merging module config with
base config where more specific config (i.e. module's) has more priority. One of such cases is merging events.

To enable reverse merge pass `ReverseMerge` modifier with specified group names to the `Config` constructor:

```php
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Config\Modifier\ReverseMerge;

$config = new Config(
    new ConfigPaths(dirname(__DIR__)),
    'dev',
    [
        ReverseMerge::groups('events', 'events-web', 'events-console'),
    ],
);

$events = $config->get('events-console'); // merged reversed
```

> Note: References to another configs use reverse modifier of root group.

### Remove elements from vendor package configuration

Sometimes it is necessary to remove some elements of vendor packages configuration. To do this,
pass `RemoveFromVendor` modifier to the `Config` constructor.

Remove specified key paths:

```php
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Config\Modifier\RemoveFromVendor;

$config = new Config(
    new ConfigPaths(dirname(__DIR__)),
    'dev',
    [
        // Remove elements `key-for-remove` and `nested→key→for-remove` from all groups in all vendor packages
        RemoveFromVendor::keys(
            ['key-for-remove'],
            ['nested', 'key', 'for-remove'],
        ),
        
        // Remove elements `a` and `b` from all groups in package `yiisoft/auth`
        RemoveFromVendor::keys(['a'], ['b'])
            ->package('yiisoft/auth'),
        
        // Remove elements `c` and `d` from groups `params` and `web` in package `yiisoft/view`
        RemoveFromVendor::keys(['c'], ['d'])
            ->package('yiisoft/view', 'params', 'web'),
        
        // Remove elements `e` and `f` from all groups in package `yiisoft/auth`
        // and from groups `params` and `web` in package `yiisoft/view`
        RemoveFromVendor::keys(['e'], ['f'])
            ->package('yiisoft/auth')
            ->package('yiisoft/view', 'params', 'web'),
    ],
);

$params = $config->get('params');
```

Remove specified configuration groups:

```php
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Config\Modifier\RemoveFromVendor;

$config = new Config(
    new ConfigPaths(dirname(__DIR__)),
    'dev',
    [
        RemoveFromVendor::groups([
            // Remove group `params` from all vendor packages
            '*' => 'params',
            
            // Remove groups `common` and `web` from all vendor packages
            '*' => ['common', 'web'],
            
            // Remove all groups from package `yiisoft/auth`
            'yiisoft/auth' => '*',
            
            // Remove groups `params` from package `yiisoft/http`
            'yiisoft/http' => 'params',
            
            // Remove groups `params` and `common` from package `yiisoft/view`
            'yiisoft/view' => ['params', 'common'],
        ]),
    ],
);
```

### Combine modifiers

`Config` supports simultaneous use of several modifiers:

```php
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Config\Modifier\RecursiveMerge;
use Yiisoft\Config\Modifier\RemoveFromVendor;
use Yiisoft\Config\Modifier\ReverseMerge;

$config = new Config(
    new ConfigPaths(dirname(__DIR__)),
    'dev',
    [
        RecursiveMerge::groups('params', 'events', 'events-web', 'events-console'),
        ReverseMerge::groups('events', 'events-web', 'events-console'),
        RemoveFromVendor::keys(
            ['key-for-remove'],
            ['nested', 'key', 'for-remove'],
        ),
    ],
);
```

## Commands

### `yii-config-copy`

The plugin adds extra `yii-config-copy` command to Composer. It copies the package config files from the vendor
to the config directory of the root package:

```shell
composer yii-config-copy <package-name> [target-path] [files]
```

Copies all config files of the `yiisoft/view` package:

```shell
# To the `config` directory
composer yii-config-copy yiisoft/view

# To the `config/my/path` directory
composer yii-config-copy yiisoft/view my/path
```

Copies the specified config files of the `yiisoft/view` package:

```shell
# To the `config` directory
composer yii-config-copy yiisoft/view / params.php web.php

# To the `config/my/path` directory and without the file extension
composer yii-config-copy yiisoft/view my/path params web
```

In order to avoid conflicts with file names, a prefix is added to the names of the copied files:
`yiisoft-view-params.php`, `yiisoft-view-web.php`.

### `yii-config-rebuild`

The `yii-config-rebuild` command updates merge plan file. This command may be used if you have added files or directories
to the application configuration file structure and these were not specified in `composer.json` of the root package.
In this case you need to add to the information about new files to `composer.json` of the root package by executing the
command:

```shell
composer yii-config-rebuild
```

### `yii-config-info`

The `yii-config-rebuild` command displays application or package configuration details.

```shell
composer yii-config-info
composer yii-config-info yiisoft/widget
```

## Documentation

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for
that. You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Config package is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Credits

The plugin is heavily inspired by [Composer config plugin](https://github.com/yiisoft/composer-config-plugin)
originally created by HiQDev (<https://hiqdev.com/>) in 2016 and then adopted by Yii.

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
