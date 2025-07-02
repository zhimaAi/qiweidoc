<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Filter;

/**
 * `Equals` filter defines a criteria for ensuring field value equals a given value.
 */
final class Equals extends Compare
{
    public static function getOperator(): string
    {
        return '=';
    }
}
