<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator;

/**
 * An interface for data objects.
 */
interface DataInterface
{
    /**
     * Get a named value.
     * @param string $name The name to get value for.
     */
    public function getValue(string $name): Result;
}
