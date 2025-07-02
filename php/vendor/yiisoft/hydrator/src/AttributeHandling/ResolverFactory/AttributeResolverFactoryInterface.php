<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\AttributeHandling\ResolverFactory;

use Yiisoft\Hydrator\Attribute\Data\DataAttributeInterface;
use Yiisoft\Hydrator\Attribute\Parameter\ParameterAttributeInterface;
use Yiisoft\Hydrator\AttributeHandling\Exception\AttributeResolverNonInstantiableException;

/**
 * An interface for attribute resolver factories.
 */
interface AttributeResolverFactoryInterface
{
    /**
     * Create an attribute resolver instance.
     * @throws AttributeResolverNonInstantiableException
     */
    public function create(DataAttributeInterface|ParameterAttributeInterface $attribute): mixed;
}
