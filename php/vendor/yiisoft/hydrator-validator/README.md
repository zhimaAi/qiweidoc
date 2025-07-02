<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii Validating Hydrator</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/hydrator-validator/v)](https://packagist.org/packages/yiisoft/hydrator-validator)
[![Total Downloads](https://poser.pugx.org/yiisoft/hydrator-validator/downloads)](https://packagist.org/packages/yiisoft/hydrator-validator)
[![Build status](https://github.com/yiisoft/hydrator-validator/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/hydrator-validator/actions/workflows/build.yml)
[![codecov](https://codecov.io/gh/yiisoft/hydrator-validator/graph/badge.svg?token=vfLtWNY7nu)](https://codecov.io/gh/yiisoft/hydrator-validator)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fhydrator-validator%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/hydrator-validator/master)
[![static analysis](https://github.com/yiisoft/hydrator-validator/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/hydrator-validator/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/hydrator-validator/coverage.svg)](https://shepherd.dev/github/yiisoft/hydrator-validator)
[![psalm-level](https://shepherd.dev/github/yiisoft/hydrator-validator/level.svg)](https://shepherd.dev/github/yiisoft/hydrator-validator)

The package provides a [hydrator](https://github.com/yiisoft/hydrator)
that also does [validation](https://github.com/yiisoft/validator), including raw data.
It's useful when input data comes from a user, and you need to validate it and then put it into DTOs.

## Requirements

- PHP 8.0 or higher.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/hydrator-validator
```

## General usage

Validating hydrator is a [hydrator](https://github.com/yiisoft/hydrator) decorator that allows to validate
raw data before passing it to the decorated hydrator and to validate object after creating or populating it.

To use it, the object being validated must implement `ValidatedInputInterface`. You can use `ValidatedInputTrait` to
easily create such object. The validation rules for raw values of the object are defined with `Validate` PHP attribute.

Example of object:

```php
use Yiisoft\Hydrator\Validator\Attribute\Validate;
use Yiisoft\Hydrator\Validator\ValidatedInputInterface;
use Yiisoft\Hydrator\Validator\ValidatedInputTrait;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Required;

final class InputDto implements ValidatedInputInterface 
{
    use ValidatedInputTrait;
    
    #[Email]
    private string $email;
    
    #[Validate(new Required())]
    private string $name;
}
```

Validation result could be obtained via `getValidationResult()` method.

Validating hydrator usage example:

```php
use Yiisoft\Hydrator\HydratorInterface;
use Yiisoft\Hydrator\Validator\ValidatingHydrator;

public function actionEdit(RequestInterface $request, ValidatingHydrator $hydrator): ResponseInterface
{
    $data = $request->getParsedBody();    
    $inputDto = $hydrator->create(InputDto::class, $data);
    
    if (!$inputDto->getValidationResult()->isValid()) {
        // Validation didn't pass :(
    }
    
    // Everything is fine. You can use $inputDto now.    
}
```

## Documentation

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Validating Hydrator is free software. It is released under the terms of the BSD License.
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
