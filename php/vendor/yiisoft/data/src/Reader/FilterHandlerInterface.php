<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader;

/**
 * Filter handler checks whether an item matches criteria defined
 * in the filter with the same operator.
 */
interface FilterHandlerInterface
{
    /**
     * Get matching filter operator.
     *
     * If the filter with such operator is active, a corresponding
     * iterable filter handler will be used during matching.
     *
     * @return string Operator.
     */
    public function getOperator(): string;
}
