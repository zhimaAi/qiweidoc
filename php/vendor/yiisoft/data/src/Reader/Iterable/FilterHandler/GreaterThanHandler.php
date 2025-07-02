<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Iterable\FilterHandler;

use DateTimeInterface;
use Yiisoft\Data\Reader\Filter\GreaterThan;

/**
 * `GreaterThan` iterable filter handler checks that the item's field value is greater than the given value.
 */
final class GreaterThanHandler extends CompareHandler
{
    public function getOperator(): string
    {
        return GreaterThan::getOperator();
    }

    protected function compare(mixed $itemValue, mixed $argumentValue): bool
    {
        if (!$itemValue instanceof DateTimeInterface) {
            return $itemValue > $argumentValue;
        }

        return $argumentValue instanceof DateTimeInterface
            && $itemValue->getTimestamp() > $argumentValue->getTimestamp();
    }
}
