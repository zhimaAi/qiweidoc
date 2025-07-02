<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\AttributeHandling\ResolverFactory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Yiisoft\Hydrator\Attribute\Data\DataAttributeInterface;
use Yiisoft\Hydrator\AttributeHandling\Exception\AttributeResolverNonInstantiableException;
use Yiisoft\Hydrator\Attribute\Parameter\ParameterAttributeInterface;

use function is_string;

/**
 * A factory for attribute resolvers that are instantiable by a container.
 */
final class ContainerAttributeResolverFactory implements AttributeResolverFactoryInterface
{
    /**
     * @param ContainerInterface $container Container to get attributes' resolvers from.
     */
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function create(DataAttributeInterface|ParameterAttributeInterface $attribute): mixed
    {
        $resolver = $attribute->getResolver();
        if (!is_string($resolver)) {
            return $resolver;
        }

        if (!$this->container->has($resolver)) {
            throw new AttributeResolverNonInstantiableException(
                sprintf(
                    'Class "%s" does not exist.',
                    $resolver,
                ),
            );
        }

        return $this->container->get($resolver);
    }
}
