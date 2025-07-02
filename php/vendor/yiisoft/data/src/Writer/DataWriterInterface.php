<?php

declare(strict_types=1);

namespace Yiisoft\Data\Writer;

/**
 * A data writer is able to write or delete data items.
 */
interface DataWriterInterface extends WriteableInterface, DeletableInterface
{
}
