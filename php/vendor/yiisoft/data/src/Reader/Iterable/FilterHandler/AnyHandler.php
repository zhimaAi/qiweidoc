<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Iterable\FilterHandler;

use Yiisoft\Data\Reader\Filter\Any;

use function in_array;

/**
 * `Any` iterable filter handler allows combining multiple sub-filters.
 * The filter matches if any of the sub-filters match.
 */
final class AnyHandler extends GroupHandler
{
    public function getOperator(): string
    {
        return Any::getOperator();
    }

    protected function checkResults(array $results): bool
    {
        return in_array(true, $results, true);
    }
}
