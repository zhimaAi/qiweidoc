<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Iterable\FilterHandler;

use DateTimeInterface;
use InvalidArgumentException;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Data\Reader\Filter\Between;
use Yiisoft\Data\Reader\FilterAssert;
use Yiisoft\Data\Reader\Iterable\IterableFilterHandlerInterface;

use function count;

/**
 * `Between` iterable filter handler checks that the item's field value
 * is between minimal and maximal values.
 */
final class BetweenHandler implements IterableFilterHandlerInterface
{
    public function getOperator(): string
    {
        return Between::getOperator();
    }

    public function match(array|object $item, array $arguments, array $iterableFilterHandlers): bool
    {
        if (count($arguments) !== 3) {
            throw new InvalidArgumentException('$arguments should contain exactly three elements.');
        }

        /** @var string $field */
        [$field, $minValue, $maxValue] = $arguments;
        FilterAssert::fieldIsString($field);

        $value = ArrayHelper::getValue($item, $field);

        if (!$value instanceof DateTimeInterface) {
            return $value >= $minValue && $value <= $maxValue;
        }

        return $minValue instanceof DateTimeInterface
            && $maxValue instanceof DateTimeInterface
            && $value->getTimestamp() >= $minValue->getTimestamp()
            && $value->getTimestamp() <= $maxValue->getTimestamp();
    }
}
