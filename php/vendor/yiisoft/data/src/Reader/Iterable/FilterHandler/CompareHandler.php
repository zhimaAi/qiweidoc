<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Iterable\FilterHandler;

use InvalidArgumentException;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Data\Reader\FilterAssert;
use Yiisoft\Data\Reader\Iterable\IterableFilterHandlerInterface;

use function count;

/**
 * Abstract `Compare` iterable filter handler compares item's field value with a given value.
 * The actual comparison is defined in {@see compare()} method implemented in child classes.
 */
abstract class CompareHandler implements IterableFilterHandlerInterface
{
    /**
     * Compare item's field value with a given value.
     *
     * @param mixed $itemValue Value of the item to compare.
     * @param mixed $argumentValue Value to compare with.
     *
     * @return bool If the comparison is true.
     */
    abstract protected function compare(mixed $itemValue, mixed $argumentValue): bool;

    public function match(array|object $item, array $arguments, array $iterableFilterHandlers): bool
    {
        if (count($arguments) !== 2) {
            throw new InvalidArgumentException('$arguments should contain exactly two elements.');
        }

        [$field, $value] = $arguments;
        FilterAssert::fieldIsString($field);

        /** @var string $field */
        return $this->compare(ArrayHelper::getValue($item, $field), $value);
    }
}
