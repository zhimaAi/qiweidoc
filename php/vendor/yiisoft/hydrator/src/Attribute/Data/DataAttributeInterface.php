<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Attribute\Data;

/**
 * An interface for data attributes (allowed only in classes). Can be used to change the way data to be assigned is
 * obtained.
 */
interface DataAttributeInterface
{
    /**
     * A matching resolver name or an instance used for processing this attribute.
     *
     * @return DataAttributeResolverInterface|string An attribute resolver name or an instance.
     */
    public function getResolver(): string|DataAttributeResolverInterface;
}
