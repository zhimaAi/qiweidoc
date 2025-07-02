<?php

declare(strict_types=1);

namespace Yiisoft\Input\Http\Attribute\Data;

use Attribute;
use Yiisoft\Hydrator\ArrayData;
use Yiisoft\Hydrator\Attribute\Data\DataAttributeInterface;

/**
 * Take data for the input DTO from the request body.
 * @psalm-import-type MapType from ArrayData
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class FromBody implements DataAttributeInterface
{
    /**
     * @param array|string|null $name The field in the request body to get data from.
     * Array means a path.
     * For example, `['user', 'name']` will get data from `$body['user']['name']`.
     * If `null`, the whole body will be used.
     * @psalm-param string|list<string>|null $name
     * @param array $map Map of the input DTO property names to the request body data keys.
     * @psalm-param MapType $map
     * @param bool $strict Whether to throw an exception if the request body contains data that isn't in the map.
     */
    public function __construct(
        private string|array|null $name = null,
        private array $map = [],
        private bool $strict = false,
    ) {
    }

    /**
     * Get the name of the field in the request body to get data from.
     *
     * Array means a path.
     * For example, `['user', 'name']` will get data from `$body['user']['name']`.
     * If `null`, the whole body will be used.
     *
     * @return array|string|null The field in the request body to get data from.
     * @psalm-return string|list<string>|null
     */
    public function getName(): string|array|null
    {
        return $this->name;
    }

    /**
     * Get the map of the input DTO property names to the request body data keys.
     *
     * @return array Map of the input DTO property names to the request body data keys.
     * @psalm-return MapType
     */
    public function getMap(): array
    {
        return $this->map;
    }

    /**
     * @return bool Whether to throw an exception if the request body contains data that isn't in the map.
     */
    public function isStrict(): bool
    {
        return $this->strict;
    }

    public function getResolver(): string
    {
        return FromBodyResolver::class;
    }
}
