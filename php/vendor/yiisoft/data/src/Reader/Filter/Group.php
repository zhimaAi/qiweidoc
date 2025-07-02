<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Filter;

use InvalidArgumentException;
use Yiisoft\Data\Reader\FilterInterface;

use function array_shift;
use function is_array;
use function is_string;
use function sprintf;

/**
 * `Group` filter is an abstract class that allows combining multiple criteria or sub-filters.
 */
abstract class Group implements FilterInterface
{
    /**
     * @var array[]|FilterInterface[] Criteria and sub-filters to use.
     */
    private array $filtersAndCriteria;

    /**
     * @param FilterInterface ...$filters Sub-filters to use.
     */
    public function __construct(FilterInterface ...$filters)
    {
        $this->filtersAndCriteria = $filters;
    }

    /**
     * Get criteria array based on current filters and criteria.
     *
     * @return array Criteria array.
     */
    public function toCriteriaArray(): array
    {
        $criteriaArray = [];

        foreach ($this->filtersAndCriteria as $filterOrCriteria) {
            if ($filterOrCriteria instanceof FilterInterface) {
                $filterOrCriteria = $filterOrCriteria->toCriteriaArray();
            }

            $criteriaArray[] = $filterOrCriteria;
        }

        return [static::getOperator(), $criteriaArray];
    }

    /**
     * Get a new instance with filters set from criteria array provided.
     *
     * ```php
     * $dataReader->withFilter((new All())->withCriteriaArray(
     *   [
     *     ['>', 'id', 88],
     *     ['or', [
     *        ['=', 'state', 2],
     *        ['like', 'name', 'eva'],
     *     ],
     *   ]
     * ));
     * ```
     *
     * @param array[] $criteriaArray Criteria array to use.
     * Instances of FilterInterface are ignored.
     *
     * @throws InvalidArgumentException If criteria array is not valid.
     *
     * @return static New instance.
     *
     * @psalm-suppress DocblockTypeContradiction Needed to allow to validation of `$criteriaArray`
     */
    public function withCriteriaArray(array $criteriaArray): static
    {
        foreach ($criteriaArray as $key => $item) {
            if (!is_array($item)) {
                throw new InvalidArgumentException(sprintf('Invalid filter on "%s" key.', $key));
            }

            $operator = array_shift($item);

            if (!is_string($operator) || $operator === '') {
                throw new InvalidArgumentException(sprintf('Invalid filter operator on "%s" key.', $key));
            }
        }

        $new = clone $this;
        $new->filtersAndCriteria = $criteriaArray;
        return $new;
    }
}
