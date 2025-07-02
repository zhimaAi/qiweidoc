<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Iterable\FilterHandler;

use DateTimeInterface;
use Yiisoft\Data\Reader\Filter\GreaterThanOrEqual;

/**
 * `GreaterThanOrEqual` iterable filter handler checks that the item's field value
 * is greater than or equal to the given value.
 */
final class GreaterThanOrEqualHandler extends CompareHandler
{
    public function getOperator(): string
    {
        return GreaterThanOrEqual::getOperator();
    }

    protected function compare(mixed $itemValue, mixed $argumentValue): bool
    {
        if (!$itemValue instanceof DateTimeInterface) {
            return $itemValue >= $argumentValue;
        }

        return $argumentValue instanceof DateTimeInterface
            && $itemValue->getTimestamp() >= $argumentValue->getTimestamp();
    }
}
