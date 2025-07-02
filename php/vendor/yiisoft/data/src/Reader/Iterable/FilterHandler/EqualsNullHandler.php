<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Iterable\FilterHandler;

use InvalidArgumentException;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Data\Reader\Filter\EqualsNull;
use Yiisoft\Data\Reader\FilterAssert;
use Yiisoft\Data\Reader\Iterable\IterableFilterHandlerInterface;

use function count;

/**
 * `EqualsNull` iterable filter handler checks that the item's field value is null.
 */
final class EqualsNullHandler implements IterableFilterHandlerInterface
{
    public function getOperator(): string
    {
        return EqualsNull::getOperator();
    }

    public function match(array|object $item, array $arguments, array $iterableFilterHandlers): bool
    {
        if (count($arguments) !== 1) {
            throw new InvalidArgumentException('$arguments should contain exactly one element.');
        }

        [$field] = $arguments;
        FilterAssert::fieldIsString($field);

        /** @var string $field */
        return ArrayHelper::getValue($item, $field) === null;
    }
}
