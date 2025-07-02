<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Filter;

/**
 * `LessThanOrEqual` filter defines a criteria for ensuring field value is less than or equal to a given value.
 */
final class LessThanOrEqual extends Compare
{
    public static function getOperator(): string
    {
        return '<=';
    }
}
