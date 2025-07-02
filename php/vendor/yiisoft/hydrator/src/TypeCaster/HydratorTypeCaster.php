<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\TypeCaster;

use ReflectionNamedType;
use ReflectionUnionType;
use Yiisoft\Hydrator\Exception\NonInstantiableException;
use Yiisoft\Hydrator\HydratorInterface;
use Yiisoft\Hydrator\Result;

use function is_array;

/**
 * Casts arrays to objects.
 */
final class HydratorTypeCaster implements TypeCasterInterface
{
    public function cast(mixed $value, TypeCastContext $context): Result
    {
        $type = $context->getReflectionType();
        $hydrator = $context->getHydrator();

        if (!is_array($value)) {
            return Result::fail();
        }

        if ($type instanceof ReflectionNamedType) {
            return $this->castInternal($value, $type, $hydrator);
        }

        if (!$type instanceof ReflectionUnionType) {
            return Result::fail();
        }

        foreach ($type->getTypes() as $t) {
            if (!$t instanceof ReflectionNamedType) {
                continue;
            }

            $result = $this->castInternal($value, $t, $hydrator);
            if ($result->isResolved()) {
                return $result;
            }
        }

        return Result::fail();
    }

    private function castInternal(array $value, ReflectionNamedType $type, HydratorInterface $hydrator): Result
    {
        if ($type->isBuiltin()) {
            return Result::fail();
        }

        $class = $type->getName();

        try {
            $object = $hydrator->create($class, $value);
        } catch (NonInstantiableException) {
            return Result::fail();
        }

        return Result::success($object);
    }
}
