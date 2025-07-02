<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Filter;

/**
 * `GreaterThan` filter defines a criteria for ensuring field value
 * is greater than a given value.
 */
final class GreaterThan extends Compare
{
    public static function getOperator(): string
    {
        return '>';
    }
}
