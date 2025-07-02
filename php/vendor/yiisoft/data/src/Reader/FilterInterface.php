<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader;

/**
 * Filter is a set of settings for modifying data reader criteria.
 */
interface FilterInterface
{
    /**
     * Get a filter operator to use in the filter criteria
     * Also used to choose {@see FilterHandlerInterface}.
     *
     * @return string Operator.
     */
    public static function getOperator(): string;

    /**
     * Get an array representation of filter criteria.
     *
     * @return array Array representation of filter criteria.
     */
    public function toCriteriaArray(): array;
}
