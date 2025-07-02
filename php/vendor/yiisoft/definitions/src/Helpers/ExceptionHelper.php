<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Helpers;

use Yiisoft\Definitions\Exception\InvalidConfigException;

use function sprintf;

/**
 * @internal
 */
final class ExceptionHelper
{
    public static function invalidArrayDefinitionKey(int|string $key): InvalidConfigException
    {
        return new InvalidConfigException(
            sprintf(
                'Invalid definition: invalid key in array definition. Only string keys are allowed, got %d.',
                $key,
            ),
        );
    }

    public static function incorrectArrayDefinitionConstructorArguments(mixed $value): InvalidConfigException
    {
        return new InvalidConfigException(
            sprintf(
                'Invalid definition: incorrect constructor arguments. Expected array, got %s.',
                get_debug_type($value),
            ),
        );
    }

    public static function incorrectArrayDefinitionMethodArguments(string $key, mixed $value): InvalidConfigException
    {
        return new InvalidConfigException(
            sprintf(
                'Invalid definition: incorrect method "%s" arguments. Expected array, got "%s". ' .
                'Probably you should wrap them into square brackets.',
                $key,
                get_debug_type($value),
            ),
        );
    }
}
