<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Filter;

/**
 * `All` filter allows combining multiple criteria or sub-filters using "and" operator.
 *
 * ```php
 * $dataReader->withFilter((new All())->withCriteriaArray(
 *   [
 *     ['>', 'id', 88],
 *     ['and', [
 *        ['=', 'state', 2],
 *        ['like', 'name', 'eva'],
 *     ],
 *   ]
 * ));
 * ```
 */
final class All extends Group
{
    public static function getOperator(): string
    {
        return 'and';
    }
}
