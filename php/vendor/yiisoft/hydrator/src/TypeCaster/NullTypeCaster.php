<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\TypeCaster;

use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use Yiisoft\Hydrator\Result;

/**
 * Configurable type caster for casting value to `null`.
 */
final class NullTypeCaster implements TypeCasterInterface
{
    public function __construct(
        private bool $null = true,
        private bool $emptyString = false,
        private bool $emptyArray = false,
    ) {
    }

    public function cast(mixed $value, TypeCastContext $context): Result
    {
        if (!$this->isAllowNull($context->getReflectionType())) {
            return Result::fail();
        }

        if (
            ($this->null && $value === null)
            || ($this->emptyString && $value === '')
            || ($this->emptyArray && $value === [])
        ) {
            return Result::success(null);
        }

        return Result::fail();
    }

    private function isAllowNull(?ReflectionType $type): bool
    {
        if ($type === null) {
            return true;
        }

        if ($type instanceof ReflectionNamedType) {
            return $type->allowsNull();
        }

        if ($type instanceof ReflectionUnionType) {
            /** @psalm-suppress RedundantConditionGivenDocblockType Needed for PHP less than 8.2 */
            foreach ($type->getTypes() as $subtype) {
                if ($subtype instanceof ReflectionNamedType && $type->allowsNull()) {
                    return true;
                }
            }
        }

        return false;
    }
}
