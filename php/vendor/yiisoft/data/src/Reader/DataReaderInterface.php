<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader;

use IteratorAggregate;

/**
 * Data reader is a data source that can do the following with data items:
 *
 * - Read
 * - Skip a number of items from the beginning
 * - Count
 * - Sort
 * - Filter
 * - Iterate with foreach
 *
 * @template TKey as array-key
 * @template TValue as array|object
 *
 * @extends ReadableDataInterface<TKey, TValue>
 * @extends IteratorAggregate<TKey, TValue>
 */
interface DataReaderInterface extends
    ReadableDataInterface,
    OffsetableDataInterface,
    CountableDataInterface,
    SortableDataInterface,
    FilterableDataInterface,
    IteratorAggregate
{
}
