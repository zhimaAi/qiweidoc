<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Attribute\Parameter;

use Attribute;

/**
 * Converts the resolved value to array of instances of the class specified in {@see Collection::$className}.
 * Non-resolved and invalid values are skipped.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class Collection implements ParameterAttributeInterface
{
    /**
     * @psalm-param class-string $className
     */
    public function __construct(
        public readonly string $className,
    ) {
    }

    public function getResolver(): string
    {
        return CollectionResolver::class;
    }
}
