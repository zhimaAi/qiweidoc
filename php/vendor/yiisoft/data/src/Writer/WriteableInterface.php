<?php

declare(strict_types=1);

namespace Yiisoft\Data\Writer;

/**
 * Writable allows writing a set of items.
 */
interface WriteableInterface
{
    /**
     * Write items specified.
     *
     * @param iterable $items Items to write.
     *
     * @throws DataWriterException If there is an error writing items.
     */
    public function write(iterable $items): void;
}
