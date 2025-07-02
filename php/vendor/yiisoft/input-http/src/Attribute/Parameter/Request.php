<?php

declare(strict_types=1);

namespace Yiisoft\Input\Http\Attribute\Parameter;

use Attribute;
use Yiisoft\Hydrator\Attribute\Parameter\ParameterAttributeInterface;

/**
 * Take data for the property or attribute from request attributes.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class Request implements ParameterAttributeInterface
{
    /**
     * @param array|string|null $name The field in the request attributes to get data from.
     * @psalm-param string|list<string>|null $name
     */
    public function __construct(
        private string|array|null $name = null
    ) {
    }

    /**
     * Get the name of the field in the request attributes to get data from.
     *
     * @psalm-return string|list<string>|null The field in the request attributes to get data from.
     */
    public function getName(): string|array|null
    {
        return $this->name;
    }

    public function getResolver(): string
    {
        return RequestResolver::class;
    }
}
