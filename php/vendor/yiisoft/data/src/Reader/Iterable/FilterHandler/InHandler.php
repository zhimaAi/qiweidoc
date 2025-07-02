<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Iterable\FilterHandler;

use Yiisoft\Data\Reader\Filter\In;

use function in_array;
use function is_array;

/**
 * `In` iterable filter handler ensures that the field value matches one of the value provided.
 */
final class InHandler extends CompareHandler
{
    public function getOperator(): string
    {
        return In::getOperator();
    }

    protected function compare(mixed $itemValue, mixed $argumentValue): bool
    {
        return is_array($argumentValue) && in_array($itemValue, $argumentValue, false);
    }
}
