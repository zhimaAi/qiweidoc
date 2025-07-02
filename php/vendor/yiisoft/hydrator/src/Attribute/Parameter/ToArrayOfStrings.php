<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Attribute\Parameter;

use Attribute;

/**
 * Casts the resolved value to array of strings.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class ToArrayOfStrings implements ParameterAttributeInterface
{
    /**
     * @param bool $trim Trim each string of array.
     * @param bool $removeEmpty Remove empty strings from array.
     * @param bool $splitResolvedValue Split non-array resolved value to array of strings by {@see $separator}.
     * @param string $separator The boundary string. It is a part of regular expression
     * so should be taken into account or properly escaped with {@see preg_quote()}.
     */
    public function __construct(
        public readonly bool $trim = false,
        public readonly bool $removeEmpty = false,
        public readonly bool $splitResolvedValue = true,
        public readonly string $separator = '\R',
    ) {
    }

    public function getResolver(): string
    {
        return ToArrayOfStringsResolver::class;
    }
}
