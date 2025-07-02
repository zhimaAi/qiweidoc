<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii Definitions</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/definitions/v)](https://packagist.org/packages/yiisoft/definitions)
[![Total Downloads](https://poser.pugx.org/yiisoft/definitions/downloads)](https://packagist.org/packages/yiisoft/definitions)
[![Build status](https://github.com/yiisoft/definitions/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/definitions/actions/workflows/build.yml)
[![Code Coverage](https://codecov.io/gh/yiisoft/definitions/graph/badge.svg?token=9srXPaWGlj)](https://codecov.io/gh/yiisoft/definitions)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdefinitions%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/definitions/master)
[![Static analysis](https://github.com/yiisoft/definitions/actions/workflows/static.yml/badge.svg?branch=master)](https://github.com/yiisoft/definitions/actions/workflows/static.yml?query=branch%3Amaster)
[![type-coverage](https://shepherd.dev/github/yiisoft/definitions/coverage.svg)](https://shepherd.dev/github/yiisoft/definitions)

The package provides syntax constructs describing a way to create and configure a service or an object.
It is used by [yiisoft/di](https://github.com/yiisoft/di) and [yiisoft/factory](https://github.com/yiisoft/factory)
but could be used in other [PSR-11](https://www.php-fig.org/psr/psr-11/) compatible packages as well.

The following are provided:

- Definitions describing services or objects to create. This includes syntax, its validation and resolving it to objects.
- References and dynamic references to point to other definitions. These include additional utility to refer to multiple
  definitions at once.

## Requirements

- PHP 8.1 or higher.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/definitions
```

## General usage

### Definitions

Definition is describing a way to create and configure a service, an object
or return any other value. It must implement `Yiisoft\Definitions\Contract\DefinitionInterface`
that has a single method `resolve(ContainerInterface $container)`. References are
typically stored in the container or a factory and are resolved into an object
at the moment of getting a service instance or creating an object.

#### `ArrayDefinition`

Array definition allows describing a service or an object declaratively:

```php
use \Yiisoft\Definitions\ArrayDefinition;

$definition = ArrayDefinition::fromConfig([
    'class' => MyServiceInterface::class,
    '__construct()' => [42], 
    '$propertyName' => 'value',
    'setName()' => ['Alex'],
]);
$object = $definition->resolve($container);
```

In the above:

- `class` contains the name of the class to be instantiated.
- `__construct()` holds an array of constructor arguments.
- The rests of the config are property values (prefixed with `$`)
  and method calls, postfixed with `()`. They are set/called
  in the order they appear in the array.

For multiple method call postfix key with unique string, for example:

```php
[
    'class' => Collector::class,
    'add()' => ['Alex'],
    'add()2' => ['Mike'],
]
```

#### `CallableDefinition`

Callable definition builds an object by executing a callable injecting
dependencies based on types used in its signature:

```php
use \Yiisoft\Definitions\CallableDefinition;

$definition = new CallableDefinition(
    fn (SomeFactory $factory) => $factory->create('args')
);
$object = $definition->resolve($container);

// or 

$definition = new CallableDefinition(
    fn () => MyFactory::create('args')
);
$object = $definition->resolve($container);

// or

$definition = new CallableDefinition(
    [MyFactory::class, 'create']
);
$object = $definition->resolve($container);
```

In the above, we use a closure, a static call and a static
method passed as array-callable. In each case, we determine
and pass dependencies based on the types of arguments in
the callable signature.

#### `ParameterDefinition`

Parameter definition resolves an object based on information from `ReflectionParameter` instance:

```php
use \Yiisoft\Definitions\ParameterDefinition;

$definition = new ParameterDefinition($reflectionParameter);
$object = $definition->resolve($container);
```

It is mostly used internally when working with callables.

#### `ValueDefinition`

Value definition resolves value passed as is:

```php
use \Yiisoft\Definitions\ValueDefinition;

$definition = new ValueDefinition(42, 'int');
$value = $definition->resolve($container); // 42
```

### References

References point to other definitions, so when defining a definition, you can use other definitions as its
dependencies:

```php
[
    InterfaceA::class => ConcreteA::class,
    'alternativeForA' => ConcreteB::class,
    MyService::class => [
        '__construct()' => [
            Reference::to('alternativeForA'),
        ],
    ],
]
```

Optional reference returns `null` when there's no corresponding definition in container:

```php
[
    MyService::class => [
        '__construct()' => [
            // If a container doesn't have definition for `EventDispatcherInterface` reference returns `null`
            // when resolving dependencies
            Reference::optional(EventDispatcherInterface::class), 
        ],
    ],
]
```

The `DynamicReference` defines a dependency to a service not defined in the container:

```php
[
   MyService::class => [
       '__construct()' => [
           DynamicReference::to([
               'class' => SomeClass::class,
               '$someProp' => 15
           ])
       ]
   ]
]
```

To pass an array of IDs as references to a property or an argument, `Yiisoft\Definitions\ReferencesArray` or
`Yiisoft\Definitions\DynamicReferencesArray` could be used:

```php
//params.php
return [
   'yiisoft/data-response' => [
       'contentFormatters' => [
           'text/html' => HtmlDataResponseFormatter::class,
           'application/xml' => XmlDataResponseFormatter::class,
           'application/json' => JsonDataResponseFormatter::class,
       ],
   ],
];

//web.php

ContentNegotiator::class => [
    '__construct()' => [
        'contentFormatters' => ReferencesArray::from($params['yiisoft/data-response']['contentFormatters']),
    ],
],
```

### Class aliases

To define another instance of a class with specific configuration, you can
use native PHP `class_alias()`:

```php
class_alias(Yiisoft\Db\Pgsql\Connection::class, 'MyPgSql');

return [
    MyPgSql::class => [ ... ]
];
```

### Definition storage

Definition storage could be used to hold and get definitions and check if a certain definition could be instantiated.
Usually it is used by an implementation using the definitions:

```php
use Yiisoft\Definitions\DefinitionStorage;

$storage = new DefinitionStorage([
    MyInterface::class => MyClass::class,
]);
$storage->setDelegateContainer($fallbackContainer);

if (!$storage->has(MyInterface::class)) {
    $buildStack = $storage->getBuildStack();
    // ...
}
```

In the above `$buildStack` will contain a stack with definition IDs in the order the latest dependency obtained would be
built.

By default, if a class is checked in `has()` and it is not explicitly defined, the storage tries to autoload it first
before failing. The storage may also work in a strict mode when everything in it should be defined explicitly:

```php
use Yiisoft\Definitions\DefinitionStorage;

$storage = new DefinitionStorage([], true);
var_dump($storage->has(EngineMarkOne::class));
```

`has()` will return `false` even if `EngineMarkOne` exists.

## Documentation

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for
that. You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Definitions is free software. It is released under the terms of the BSD License.
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
