<?php

declare(strict_types=1);

namespace Yiisoft\Data\Writer;

/**
 * Deletable allows deleting a set of items.
 */
interface DeletableInterface
{
    /**
     * Delete items specified.
     *
     * @param iterable $items Items to delete.
     *
     * @throws DataWriterException If there is an error deleting items.
     */
    public function delete(iterable $items): void;
}
