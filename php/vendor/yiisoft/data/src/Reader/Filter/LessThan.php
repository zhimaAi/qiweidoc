<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Filter;

/**
 * `LessThan` filter defines a criteria for ensuring field value is less than a given value.
 */
final class LessThan extends Compare
{
    public static function getOperator(): string
    {
        return '<';
    }
}
