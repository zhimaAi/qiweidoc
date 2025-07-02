<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader;

/**
 * Data that could be filtered.
 *
 * Filtering is done in two steps:
 *
 * - Criteria of reading data is modified according to {@see FilterInterface}
 * - Resulting items are iterated over and filter handler matching
 *   {@see FilterInterface::getOperator()} is applied
 *
 * For performance reasons prefer filtering as many items as possible in the first step.
 */
interface FilterableDataInterface
{
    /**
     * Returns new instance with data reading criteria set.
     *
     * @param FilterInterface $filter Data reading criteria.
     *
     * @return static New instance.
     * @psalm-return $this
     */
    public function withFilter(FilterInterface $filter): static;

    /**
     * Returns new instance with additional handlers set.
     *
     * @param FilterHandlerInterface ...$filterHandlers Additional filter handlers.
     *
     * @return static New instance.
     * @psalm-return $this
     */
    public function withFilterHandlers(FilterHandlerInterface ...$filterHandlers): static;
}
