<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator;

use function array_key_exists;

/**
 * Provides a mapping of object property names to keys in the data array.
 *
 * @psalm-import-type MapType from ArrayData
 */
final class ObjectMap
{
    /**
     * @param array $map Object property names mapped to keys in the data array that hydrator will use when hydrating
     *  an object.
     * @psalm-param MapType $map
     */
    public function __construct(
        public readonly array $map
    ) {
    }

    /**
     * Returns a path for a given property name or null if mapping dosen't exist.
     *
     * @psalm-return string|list<string>|ObjectMap|null
     */
    public function getPath(string $name): string|array|self|null
    {
        return $this->map[$name] ?? null;
    }

    /**
     * Returns a list of property names for which mapping is set.
     *
     * @return string[] List of property names.
     * @psalm-return list<string>
     */
    public function getNames(): array
    {
        return array_keys($this->map);
    }

    /**
     * Checks if a given property name exists in the mapping array.
     *
     * @param string $name The property name.
     * @return bool Whether the property name exists in the mapping array.
     */
    public function exists(string $name): bool
    {
        return array_key_exists($name, $this->map);
    }
}
