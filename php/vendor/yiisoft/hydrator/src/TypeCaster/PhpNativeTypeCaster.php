<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\TypeCaster;

use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use Stringable;
use Yiisoft\Hydrator\Result;
use Yiisoft\Strings\NumericHelper;

use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_string;

/**
 * Casts value to a type obtained from {@see ReflectionType} passed.
 */
final class PhpNativeTypeCaster implements TypeCasterInterface
{
    public function cast(mixed $value, TypeCastContext $context): Result
    {
        $type = $context->getReflectionType();

        if ($type === null) {
            return Result::success($value);
        }

        if (!$type instanceof ReflectionNamedType && !$type instanceof ReflectionUnionType) {
            return Result::fail();
        }

        $types = $type instanceof ReflectionNamedType
            ? [$type]
            : array_filter(
                $type->getTypes(),
                static fn(mixed $type) => $type instanceof ReflectionNamedType,
            );

        /**
         * Find the best type name and value type match.
         * Example:
         * - when pass `42` to `int|string` type, `int` will be used;
         * - when pass `"42"` to `int|string` type, `string` will be used.
         */
        foreach ($types as $t) {
            if ($value === null && $t->allowsNull()) {
                return Result::success(null);
            }
            if (!$t->isBuiltin()) {
                continue;
            }
            switch ($t->getName()) {
                case 'string':
                    if (is_string($value)) {
                        return Result::success($value);
                    }
                    break;

                case 'int':
                    if (is_int($value)) {
                        return Result::success($value);
                    }
                    break;

                case 'float':
                    if (is_float($value)) {
                        return Result::success($value);
                    }
                    break;

                case 'bool':
                    if (is_bool($value)) {
                        return Result::success($value);
                    }
                    break;

                case 'array':
                    if (is_array($value)) {
                        return Result::success($value);
                    }
                    break;
            }
        }

        foreach ($types as $t) {
            if (!$t->isBuiltin()) {
                $class = $t->getName();
                if ($value instanceof $class) {
                    return Result::success($value);
                }
                continue;
            }
            switch ($t->getName()) {
                case 'string':
                    if (is_scalar($value) || $value === null || $value instanceof Stringable) {
                        return Result::success((string) $value);
                    }
                    break;

                case 'int':
                    if (is_bool($value) || is_float($value) || $value === null) {
                        return Result::success((int) $value);
                    }
                    if ($value instanceof Stringable || is_string($value)) {
                        return Result::success((int) NumericHelper::normalize($value));
                    }
                    break;

                case 'float':
                    if (is_int($value) || is_bool($value) || $value === null) {
                        return Result::success((float) $value);
                    }
                    if ($value instanceof Stringable || is_string($value)) {
                        return Result::success((float) NumericHelper::normalize($value));
                    }
                    break;

                case 'bool':
                    if (is_scalar($value) || $value === null || is_array($value) || is_object($value)) {
                        return Result::success((bool) $value);
                    }
                    break;
            }
        }

        return Result::fail();
    }
}
