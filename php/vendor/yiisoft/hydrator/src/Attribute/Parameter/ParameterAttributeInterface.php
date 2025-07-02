<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Attribute\Parameter;

/**
 * An interface for parameters' attributes (allowed only in class properties and constructor parameters). Can be used
 * for getting value (e. g, from request) or type casting value.
 */
interface ParameterAttributeInterface
{
    /**
     * A matching resolver name or an instance used for processing this attribute.
     *
     * @return ParameterAttributeResolverInterface|string An attribute resolver name or an instance.
     */
    public function getResolver(): string|ParameterAttributeResolverInterface;
}
