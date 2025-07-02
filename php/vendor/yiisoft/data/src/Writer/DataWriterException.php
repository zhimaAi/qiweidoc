<?php

declare(strict_types=1);

namespace Yiisoft\Data\Writer;

use RuntimeException;

/**
 * Exception occurred during writing or deleting data.
 *
 * @see DataWriterInterface
 */
class DataWriterException extends RuntimeException
{
}
