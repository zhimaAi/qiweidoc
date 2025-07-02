<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Filter;

/**
 * `Any` filter allows combining multiple criteria or sub-filters using "or" operator.
 *
 * ```php
 * $dataReader->withFilter((new Any())->withCriteriaArray(
 *   [
 *     ['>', 'id', 88],
 *     ['or', [
 *        ['=', 'state', 2],
 *        ['like', 'name', 'eva'],
 *     ],
 *   ]
 * ));
 * ```
 */
final class Any extends Group
{
    public static function getOperator(): string
    {
        return 'or';
    }
}
