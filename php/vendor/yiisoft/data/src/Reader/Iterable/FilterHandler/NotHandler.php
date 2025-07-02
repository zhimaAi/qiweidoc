<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Iterable\FilterHandler;

use InvalidArgumentException;
use Yiisoft\Data\Reader\Filter\Not;
use Yiisoft\Data\Reader\FilterAssert;
use Yiisoft\Data\Reader\Iterable\IterableFilterHandlerInterface;

use function array_shift;
use function count;
use function is_array;
use function is_string;
use function sprintf;

/**
 * `Not` iterable filter handler negates another filter.
 */
final class NotHandler implements IterableFilterHandlerInterface
{
    public function getOperator(): string
    {
        return Not::getOperator();
    }

    public function match(array|object $item, array $arguments, array $iterableFilterHandlers): bool
    {
        if (count($arguments) !== 1) {
            throw new InvalidArgumentException('$arguments should contain exactly one element.');
        }

        [$values] = $arguments;

        if (!is_array($values)) {
            throw new InvalidArgumentException(sprintf(
                'The values should be array. The %s is received.',
                get_debug_type($values),
            ));
        }

        if (empty($values)) {
            throw new InvalidArgumentException('At least operator should be provided.');
        }

        $operator = array_shift($values);

        if (!is_string($operator)) {
            throw new InvalidArgumentException(sprintf(
                'The operator should be string. The %s is received.',
                get_debug_type($operator),
            ));
        }

        if ($operator === '') {
            throw new InvalidArgumentException('The operator string cannot be empty.');
        }

        /** @var mixed $filterHandler */
        $filterHandler = $iterableFilterHandlers[$operator] ?? null;

        if ($filterHandler === null) {
            throw new InvalidArgumentException(sprintf('"%s" operator is not supported.', $operator));
        }

        FilterAssert::isIterableFilterHandlerInterface($filterHandler);
        /** @var IterableFilterHandlerInterface $filterHandler */

        return !$filterHandler->match($item, $values, $iterableFilterHandlers);
    }
}
