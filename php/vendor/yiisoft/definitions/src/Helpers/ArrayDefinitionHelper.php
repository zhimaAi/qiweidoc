<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Helpers;

use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\Exception\InvalidConfigException;

use function is_array;
use function is_string;

final class ArrayDefinitionHelper
{
    /**
     * @throws InvalidConfigException
     */
    public static function merge(array ...$configs): array
    {
        $result = array_shift($configs) ?: [];
        while (!empty($configs)) {
            foreach (array_shift($configs) as $key => $value) {
                if (!is_string($key)) {
                    throw ExceptionHelper::invalidArrayDefinitionKey($key);
                }

                if (!isset($result[$key])) {
                    $result[$key] = $value;
                    continue;
                }

                if ($key === ArrayDefinition::CONSTRUCTOR) {
                    if (!is_array($value)) {
                        throw ExceptionHelper::incorrectArrayDefinitionConstructorArguments($value);
                    }
                    if (!is_array($result[$key])) {
                        throw ExceptionHelper::incorrectArrayDefinitionConstructorArguments($result[$key]);
                    }
                    $result[$key] = self::mergeArguments($result[$key], $value);
                    continue;
                }

                if (str_ends_with($key, '()')) {
                    if (!is_array($value)) {
                        throw ExceptionHelper::incorrectArrayDefinitionMethodArguments($key, $value);
                    }
                    if (!is_array($result[$key])) {
                        throw ExceptionHelper::incorrectArrayDefinitionMethodArguments($key, $result[$key]);
                    }
                    $result[$key] = self::mergeArguments($result[$key], $value);
                    continue;
                }

                $result[$key] = $value;
            }
        }

        return $result;
    }

    public static function mergeArguments(array $argumentsA, array $argumentsB): array
    {
        foreach ($argumentsB as $name => $argument) {
            $argumentsA[$name] = $argument;
        }

        return $argumentsA;
    }
}
