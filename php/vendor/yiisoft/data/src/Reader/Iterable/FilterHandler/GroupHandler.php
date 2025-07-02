<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Iterable\FilterHandler;

use InvalidArgumentException;
use Yiisoft\Data\Reader\FilterAssert;
use Yiisoft\Data\Reader\Iterable\IterableFilterHandlerInterface;

use function array_shift;
use function count;
use function is_array;
use function is_string;
use function sprintf;

/**
 * Abstract `Group` iterable filter handler allows to combine runs of several iterable filters.
 * How to interpret results is determined by {@see checkResults()} implemented in child
 * classes.
 */
abstract class GroupHandler implements IterableFilterHandlerInterface
{
    /**
     * Return final decision for the match based on sub-filter match results.
     *
     * @param bool[] $results Sub-filter match results.
     *
     * @return bool Final result.
     */
    abstract protected function checkResults(array $results): bool;

    public function match(array|object $item, array $arguments, array $iterableFilterHandlers): bool
    {
        if (count($arguments) !== 1) {
            throw new InvalidArgumentException('$arguments should contain exactly one element.');
        }

        [$subFilters] = $arguments;

        if (!is_array($subFilters)) {
            throw new InvalidArgumentException(sprintf(
                'The sub filters should be array. The %s is received.',
                get_debug_type($subFilters),
            ));
        }

        $results = [];

        foreach ($subFilters as $subFilter) {
            if (!is_array($subFilter)) {
                throw new InvalidArgumentException(sprintf(
                    'The sub filter should be array. The %s is received.',
                    get_debug_type($subFilter),
                ));
            }

            if (empty($subFilter)) {
                throw new InvalidArgumentException('At least operator should be provided.');
            }

            $operator = array_shift($subFilter);

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

            $results[] = $filterHandler->match($item, $subFilter, $iterableFilterHandlers);
        }

        return $this->checkResults($results);
    }
}
