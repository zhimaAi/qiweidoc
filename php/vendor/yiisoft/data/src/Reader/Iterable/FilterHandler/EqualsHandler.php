<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Iterable\FilterHandler;

use DateTimeInterface;
use Yiisoft\Data\Reader\Filter\Equals;

/**
 * `Equals` iterable filter handler checks that the item's field value matches given value.
 */
final class EqualsHandler extends CompareHandler
{
    public function getOperator(): string
    {
        return Equals::getOperator();
    }

    protected function compare(mixed $itemValue, mixed $argumentValue): bool
    {
        if (!$itemValue instanceof DateTimeInterface) {
            return $itemValue == $argumentValue;
        }

        return $argumentValue instanceof DateTimeInterface
            && $itemValue->getTimestamp() === $argumentValue->getTimestamp();
    }
}
