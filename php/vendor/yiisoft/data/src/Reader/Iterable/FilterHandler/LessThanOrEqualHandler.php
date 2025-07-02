<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Iterable\FilterHandler;

use DateTimeInterface;
use Yiisoft\Data\Reader\Filter\LessThanOrEqual;

/**
 * `LessThanOrEqual` iterable filter handler checks that the item's field value
 * is less than or equal to the given value.
 */
final class LessThanOrEqualHandler extends CompareHandler
{
    public function getOperator(): string
    {
        return LessThanOrEqual::getOperator();
    }

    protected function compare(mixed $itemValue, mixed $argumentValue): bool
    {
        if (!$itemValue instanceof DateTimeInterface) {
            return $itemValue <= $argumentValue;
        }

        return $argumentValue instanceof DateTimeInterface
            && $itemValue->getTimestamp() <= $argumentValue->getTimestamp();
    }
}
