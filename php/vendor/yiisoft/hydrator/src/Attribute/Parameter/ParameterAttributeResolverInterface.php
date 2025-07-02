<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Attribute\Parameter;

use Yiisoft\Hydrator\AttributeHandling\ParameterAttributeResolveContext;
use Yiisoft\Hydrator\Result;

/**
 * An interface for resolvers of attributes that implement {@see ParameterAttributeInterface}.
 */
interface ParameterAttributeResolverInterface
{
    /**
     * Returns the resolved from specified attribute value object.
     *
     * @param ParameterAttributeInterface $attribute The attribute to be resolved.
     * @param ParameterAttributeResolveContext $context The context of value resolving from attribute.
     *
     * @return Result The resolved from specified attribute value object.
     */
    public function getParameterValue(
        ParameterAttributeInterface $attribute,
        ParameterAttributeResolveContext $context
    ): Result;
}
