<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\AttributeHandling\ResolverFactory;

use ReflectionClass;
use Yiisoft\Hydrator\Attribute\Data\DataAttributeInterface;
use Yiisoft\Hydrator\AttributeHandling\Exception\AttributeResolverNonInstantiableException;
use Yiisoft\Hydrator\Attribute\Parameter\ParameterAttributeInterface;

use function is_string;

/**
 * A factory for attribute resolvers that are instantiable via reflection.
 */
final class ReflectionAttributeResolverFactory implements AttributeResolverFactoryInterface
{
    public function create(DataAttributeInterface|ParameterAttributeInterface $attribute): object
    {
        $resolver = $attribute->getResolver();
        if (!is_string($resolver)) {
            return $resolver;
        }

        if (!class_exists($resolver)) {
            throw new AttributeResolverNonInstantiableException(
                sprintf(
                    'Class "%s" does not exist.',
                    $resolver,
                ),
            );
        }

        $reflectionClass = new ReflectionClass($resolver);
        if ($reflectionClass->isAbstract()) {
            throw new AttributeResolverNonInstantiableException(
                sprintf(
                    '"%s" is not instantiable because it is abstract.',
                    $reflectionClass->getName(),
                ),
            );
        }

        $constructor = $reflectionClass->getConstructor();
        if ($constructor !== null) {
            if (!$constructor->isPublic()) {
                throw new AttributeResolverNonInstantiableException(
                    sprintf(
                        'Class "%s" is not instantiable because of non-public constructor.',
                        $constructor->getDeclaringClass()->getName(),
                    ),
                );
            }

            if ($constructor->getNumberOfRequiredParameters() > 0) {
                throw new AttributeResolverNonInstantiableException(
                    sprintf(
                        'Class "%s" cannot be instantiated because it has %d required parameters in constructor.',
                        $constructor->getDeclaringClass()->getName(),
                        $constructor->getNumberOfRequiredParameters(),
                    )
                );
            }
        }

        return $reflectionClass->newInstance();
    }
}
