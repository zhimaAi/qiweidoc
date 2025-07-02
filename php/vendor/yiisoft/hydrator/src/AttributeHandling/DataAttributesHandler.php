<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\AttributeHandling;

use ReflectionAttribute;
use ReflectionClass;
use RuntimeException;
use Yiisoft\Hydrator\AttributeHandling\ResolverFactory\AttributeResolverFactoryInterface;
use Yiisoft\Hydrator\Attribute\Data\DataAttributeInterface;
use Yiisoft\Hydrator\Attribute\Data\DataAttributeResolverInterface;
use Yiisoft\Hydrator\DataInterface;

/**
 * Handles data attributes that implement {@see DataAttributeInterface}.
 *
 * @internal
 */
final class DataAttributesHandler
{
    public function __construct(
        private AttributeResolverFactoryInterface $attributeResolverFactory,
    ) {
    }

    /**
     * Handle data attributes.
     *
     * @param ReflectionClass $reflectionClass Reflection of class to attributes handle.
     * @param DataInterface $data Current data object ({@see DataInterface}).
     *
     * @psalm-param ReflectionAttribute<DataAttributeInterface>[] $reflectionAttributes
     */
    public function handle(ReflectionClass $reflectionClass, DataInterface $data): DataInterface
    {
        $reflectionAttributes = $reflectionClass->getAttributes(
            DataAttributeInterface::class,
            ReflectionAttribute::IS_INSTANCEOF
        );

        foreach ($reflectionAttributes as $reflectionAttribute) {
            $attribute = $reflectionAttribute->newInstance();

            $resolver = $this->attributeResolverFactory->create($attribute);
            if (!$resolver instanceof DataAttributeResolverInterface) {
                throw new RuntimeException(
                    sprintf(
                        'Data attribute resolver "%s" must implement "%s".',
                        get_debug_type($resolver),
                        DataAttributeResolverInterface::class,
                    ),
                );
            }

            $data = $resolver->prepareData($attribute, $data);
        }

        return $data;
    }
}
