<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Filter;

/**
 * `GreaterThanOrEqual` filter defines a criteria for ensuring field value
 * is greater than or equal to a given value.
 */
final class GreaterThanOrEqual extends Compare
{
    public static function getOperator(): string
    {
        return '>=';
    }
}
