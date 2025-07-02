<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Attribute\Parameter;

use BackedEnum;
use ReflectionEnum;
use ReflectionNamedType;
use Yiisoft\Hydrator\AttributeHandling\Exception\UnexpectedAttributeException;
use Yiisoft\Hydrator\AttributeHandling\ParameterAttributeResolveContext;
use Yiisoft\Hydrator\DataInterface;
use Yiisoft\Hydrator\Exception\NonInstantiableException;
use Yiisoft\Hydrator\HydratorInterface;
use Yiisoft\Hydrator\Result;

final class CollectionResolver implements ParameterAttributeResolverInterface
{
    public function getParameterValue(
        ParameterAttributeInterface $attribute,
        ParameterAttributeResolveContext $context,
    ): Result {
        if (!$attribute instanceof Collection) {
            throw new UnexpectedAttributeException(Collection::class, $attribute);
        }

        if (!$context->isResolved()) {
            return Result::fail();
        }

        $resolvedValue = $context->getResolvedValue();
        if (!is_iterable($resolvedValue)) {
            return Result::fail();
        }

        if (is_a($attribute->className, BackedEnum::class, true)) {
            /**
             * @psalm-suppress ArgumentTypeCoercion Because class name is backed enumeration name.
             */
            $collection = $this->createCollectionOfBackedEnums($resolvedValue, $attribute->className);
        } else {
            $collection = $this->createCollectionOfObjects(
                $resolvedValue,
                $context->getHydrator(),
                $attribute->className
            );
        }

        return Result::success($collection);
    }

    /**
     * @psalm-param class-string $className
     * @return object[]
     */
    private function createCollectionOfObjects(
        iterable $resolvedValue,
        HydratorInterface $hydrator,
        string $className
    ): array {
        $collection = [];
        foreach ($resolvedValue as $item) {
            if (!is_array($item) && !$item instanceof DataInterface) {
                continue;
            }

            try {
                $collection[] = $hydrator->create($className, $item);
            } catch (NonInstantiableException) {
                continue;
            }
        }
        return $collection;
    }

    /**
     * @psalm-param class-string<BackedEnum> $className
     * @return BackedEnum[]
     */
    private function createCollectionOfBackedEnums(iterable $resolvedValue, string $className): array
    {
        $collection = [];
        $isStringBackedEnum = $this->isStringBackedEnum($className);
        foreach ($resolvedValue as $item) {
            if ($item instanceof $className) {
                $collection[] = $item;
                continue;
            }

            if (is_string($item) || is_int($item)) {
                $enum = $className::tryFrom($isStringBackedEnum ? (string) $item : (int) $item);
                if ($enum !== null) {
                    $collection[] = $enum;
                }
            }
        }
        return $collection;
    }

    /**
     * @psalm-param class-string<BackedEnum> $className
     */
    private function isStringBackedEnum(string $className): bool
    {
        /** @var ReflectionNamedType $backingType */
        $backingType = (new ReflectionEnum($className))->getBackingType();
        return $backingType->getName() === 'string';
    }
}
