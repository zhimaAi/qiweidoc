<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Iterable\FilterHandler;

use Yiisoft\Data\Reader\Filter\All;

use function in_array;

/**
 * `All` iterable filter handler allows combining multiple sub-filters.
 * The filter matches only if all the sub-filters match.
 */
final class AllHandler extends GroupHandler
{
    public function getOperator(): string
    {
        return All::getOperator();
    }

    protected function checkResults(array $results): bool
    {
        return !in_array(false, $results, true);
    }
}
