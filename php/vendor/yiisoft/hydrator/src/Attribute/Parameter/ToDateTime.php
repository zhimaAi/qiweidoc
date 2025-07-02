<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Attribute\Parameter;

use Attribute;
use IntlDateFormatter;

/**
 * Converts the resolved value to `DateTimeImmutable` object. Non-resolved and invalid values are skipped.
 *
 * @psalm-type IntlDateFormatterFormat = IntlDateFormatter::FULL | IntlDateFormatter::LONG | IntlDateFormatter::MEDIUM | IntlDateFormatter::SHORT | IntlDateFormatter::NONE
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class ToDateTime implements ParameterAttributeInterface
{
    /**
     * @psalm-param IntlDateFormatterFormat|null $dateType
     * @psalm-param IntlDateFormatterFormat|null $timeType
     * @psalm-param non-empty-string|null $timeZone
     */
    public function __construct(
        public readonly ?string $format = null,
        public readonly ?int $dateType = null,
        public readonly ?int $timeType = null,
        public readonly ?string $timeZone = null,
        public readonly ?string $locale = null,
    ) {
    }

    public function getResolver(): string
    {
        return ToDateTimeResolver::class;
    }
}
