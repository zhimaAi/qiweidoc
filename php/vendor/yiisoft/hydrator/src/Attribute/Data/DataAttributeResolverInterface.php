<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Attribute\Data;

use Yiisoft\Hydrator\DataInterface;

/**
 * An interface for resolvers of attributes that implement {@see DataAttributeInterface}.
 */
interface DataAttributeResolverInterface
{
    /**
     * Prepare a data object ({@see DataInterface}) that used for hydration.
     *
     * @param DataAttributeInterface $attribute The attribute to be resolved.
     * @param DataInterface $data Current data object ({@see DataInterface}).
     */
    public function prepareData(DataAttributeInterface $attribute, DataInterface $data): DataInterface;
}
