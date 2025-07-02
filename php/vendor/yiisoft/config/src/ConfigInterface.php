<?php

declare(strict_types=1);

namespace Yiisoft\Config;

/**
 * Provides methods for loading configuration of the groups.
 */
interface ConfigInterface
{
    /**
     * Returns the configuration of the group.
     *
     * @param string $group The configuration group name.
     *
     * @return array The configuration of the group.
     */
    public function get(string $group): array;

    /**
     * Checks whether the configuration group exists.
     *
     * @param string $group The configuration group name.
     *
     * @return bool Whether the configuration group exists.
     */
    public function has(string $group): bool;
}
