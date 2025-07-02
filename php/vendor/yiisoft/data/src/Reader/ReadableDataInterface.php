<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader;

use InvalidArgumentException;

/**
 * Readable data is a data set that could be read up to number of items
 * defined by limit either one by one or by getting an iterator.
 *
 * @template TKey as array-key
 * @template TValue as array|object
 */
interface ReadableDataInterface
{
    /**
     * Get a new instance with limit set.
     *
     * @param int $limit Limit. 0 means "no limit".
     *
     * @throws InvalidArgumentException If limit is less than 0.
     *
     * @return static New instance.
     * @psalm-return $this
     */
    public function withLimit(int $limit): static;

    /**
     * Get iterable for the data set.
     *
     * @return iterable Iterable for the data.
     * @psalm-return iterable<TKey, TValue>
     */
    public function read(): iterable;

    /**
     * Get a next item from the data set.
     *
     * @return array|object|null An item or null if there is none.
     * @psalm-return TValue|null
     */
    public function readOne(): array|object|null;
}
