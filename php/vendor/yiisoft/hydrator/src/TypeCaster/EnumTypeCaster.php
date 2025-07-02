<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\TypeCaster;

use BackedEnum;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionUnionType;
use Stringable;
use UnitEnum;
use Yiisoft\Hydrator\Result;

use function is_a;
use function is_scalar;

/**
 * Casts values to enumerations.
 */
final class EnumTypeCaster implements TypeCasterInterface
{
    public function cast(mixed $value, TypeCastContext $context): Result
    {
        $type = $context->getReflectionType();

        if ($type instanceof ReflectionNamedType) {
            return $this->castInternal($value, $type);
        }

        if (!$type instanceof ReflectionUnionType) {
            return Result::fail();
        }

        foreach ($type->getTypes() as $t) {
            if (!$t instanceof ReflectionNamedType) {
                continue;
            }

            $result = $this->castInternal($value, $t);
            if ($result->isResolved()) {
                return $result;
            }
        }

        return Result::fail();
    }

    private function castInternal(mixed $value, ReflectionNamedType $type): Result
    {
        $enumClass = $type->getName();
        if (!$this->isEnum($enumClass)) {
            return Result::fail();
        }

        if ($value instanceof $enumClass) {
            return Result::success($value);
        }

        if (!$this->isBackedEnum($enumClass)) {
            return Result::fail();
        }

        $enumValue = $this->isStringEnum($enumClass)
            ? $this->tryCastToString($value)
            : $this->tryCastToInt($value);
        if ($enumValue === null) {
            return Result::fail();
        }

        $enum = $enumClass::tryFrom($enumValue);
        if ($enum === null) {
            return Result::fail();
        }

        return Result::success($enum);
    }

    /**
     * @psalm-assert-if-true class-string<UnitEnum> $class
     */
    private function isEnum(string $class): bool
    {
        return is_a($class, UnitEnum::class, true);
    }

    /**
     * @psalm-param class-string<UnitEnum> $class
     * @psalm-assert-if-true class-string<BackedEnum> $class
     */
    private function isBackedEnum(string $class): bool
    {
        return is_a($class, BackedEnum::class, true);
    }

    /**
     * @psalm-param class-string<BackedEnum> $class
     */
    private function isStringEnum(string $class): bool
    {
        $reflection = new ReflectionEnum($class);

        /**
         * @var ReflectionNamedType $type
         */
        $type = $reflection->getBackingType();

        return $type->getName() === 'string';
    }

    private function tryCastToString(mixed $value): ?string
    {
        if (is_scalar($value) || $value === null || $value instanceof Stringable) {
            return (string) $value;
        }
        return null;
    }

    private function tryCastToInt(mixed $value): ?int
    {
        if (is_scalar($value) || $value === null) {
            return (int) $value;
        }
        if ($value instanceof Stringable) {
            return (int) (string) $value;
        }
        return null;
    }
}
