<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Iterable\FilterHandler;

use InvalidArgumentException;
use Yiisoft\Data\Reader\Filter\EqualsEmpty;
use Yiisoft\Data\Reader\FilterAssert;
use Yiisoft\Data\Reader\Iterable\IterableFilterHandlerInterface;

use function count;

/**
 * `EqualsEmpty` iterable filter handler checks that the item's field value is empty.
 */
final class EqualsEmptyHandler implements IterableFilterHandlerInterface
{
    public function getOperator(): string
    {
        return EqualsEmpty::getOperator();
    }

    public function match(array|object $item, array $arguments, array $iterableFilterHandlers): bool
    {
        if (count($arguments) !== 1) {
            throw new InvalidArgumentException('$arguments should contain exactly one element.');
        }

        [$field] = $arguments;
        FilterAssert::fieldIsString($field);

        /** @var string $field */
        return empty($item[$field]);
    }
}
