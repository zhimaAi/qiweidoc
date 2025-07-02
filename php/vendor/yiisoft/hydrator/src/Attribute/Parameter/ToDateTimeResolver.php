<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Attribute\Parameter;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use IntlDateFormatter;
use ReflectionNamedType;
use ReflectionUnionType;
use Yiisoft\Hydrator\AttributeHandling\Exception\UnexpectedAttributeException;
use Yiisoft\Hydrator\AttributeHandling\ParameterAttributeResolveContext;
use Yiisoft\Hydrator\Result;

/**
 * @psalm-import-type IntlDateFormatterFormat from ToDateTime
 */
final class ToDateTimeResolver implements ParameterAttributeResolverInterface
{
    /**
     * @psalm-param IntlDateFormatterFormat $dateType
     * @psalm-param IntlDateFormatterFormat $timeType
     * @psalm-param non-empty-string|null $timeZone
     */
    public function __construct(
        private readonly ?string $format = null,
        private readonly int $dateType = IntlDateFormatter::SHORT,
        private readonly int $timeType = IntlDateFormatter::SHORT,
        private readonly ?string $timeZone = null,
        private readonly ?string $locale = null,
    ) {
    }

    public function getParameterValue(
        ParameterAttributeInterface $attribute,
        ParameterAttributeResolveContext $context
    ): Result {
        if (!$attribute instanceof ToDateTime) {
            throw new UnexpectedAttributeException(ToDateTime::class, $attribute);
        }

        if (!$context->isResolved()) {
            return Result::fail();
        }

        $resolvedValue = $context->getResolvedValue();
        $shouldBeMutable = $this->shouldResultBeMutable($context);

        if ($resolvedValue instanceof DateTimeInterface) {
            return $this->createSuccessResult($resolvedValue, $shouldBeMutable);
        }

        $timeZone = $attribute->timeZone ?? $this->timeZone;
        if ($timeZone !== null) {
            $timeZone = new DateTimeZone($timeZone);
        }

        if (is_int($resolvedValue)) {
            return Result::success(
                $this->makeDateTimeFromTimestamp($resolvedValue, $timeZone, $shouldBeMutable)
            );
        }

        if (is_string($resolvedValue) && !empty($resolvedValue)) {
            $format = $attribute->format ?? $this->format;
            if (is_string($format) && str_starts_with($format, 'php:')) {
                return $this->parseWithPhpFormat($resolvedValue, substr($format, 4), $timeZone, $shouldBeMutable);
            }
            return $this->parseWithIntlFormat(
                $resolvedValue,
                $format,
                $attribute->dateType ?? $this->dateType,
                $attribute->timeType ?? $this->timeType,
                $timeZone,
                $attribute->locale ?? $this->locale,
                $shouldBeMutable,
            );
        }

        return Result::fail();
    }

    /**
     * @psalm-param non-empty-string $resolvedValue
     */
    private function parseWithPhpFormat(
        string $resolvedValue,
        string $format,
        ?DateTimeZone $timeZone,
        bool $shouldBeMutable,
    ): Result {
        $date = $shouldBeMutable
            ? DateTime::createFromFormat($format, $resolvedValue, $timeZone)
            : DateTimeImmutable::createFromFormat($format, $resolvedValue, $timeZone);
        if ($date === false) {
            return Result::fail();
        }

        $errors = DateTimeImmutable::getLastErrors();
        if (!empty($errors['warning_count'])) {
            return Result::fail();
        }

        // If no time was provided in the format string set time to 0
        if (!strpbrk($format, 'aAghGHisvuU')) {
            $date = $date->setTime(0, 0);
        }

        return Result::success($date);
    }

    /**
     * @psalm-param non-empty-string $resolvedValue
     * @psalm-param IntlDateFormatterFormat $dateType
     * @psalm-param IntlDateFormatterFormat $timeType
     */
    private function parseWithIntlFormat(
        string $resolvedValue,
        ?string $format,
        int $dateType,
        int $timeType,
        ?DateTimeZone $timeZone,
        ?string $locale,
        bool $shouldBeMutable,
    ): Result {
        $formatter = $format === null
            ? new IntlDateFormatter($locale, $dateType, $timeType, $timeZone)
            : new IntlDateFormatter(
                $locale,
                IntlDateFormatter::NONE,
                IntlDateFormatter::NONE,
                $timeZone,
                pattern: $format
            );
        $formatter->setLenient(false);
        $timestamp = $formatter->parse($resolvedValue);
        return is_int($timestamp)
            ? Result::success($this->makeDateTimeFromTimestamp($timestamp, $timeZone, $shouldBeMutable))
            : Result::fail();
    }

    private function makeDateTimeFromTimestamp(
        int $timestamp,
        ?DateTimeZone $timeZone,
        bool $shouldBeMutable
    ): DateTimeInterface {
        /**
         * @psalm-suppress InvalidNamedArgument Psalm bug: https://github.com/vimeo/psalm/issues/10872
         */
        return $shouldBeMutable
            ? (new DateTime(timezone: $timeZone))->setTimestamp($timestamp)
            : (new DateTimeImmutable(timezone: $timeZone))->setTimestamp($timestamp);
    }

    private function createSuccessResult(DateTimeInterface $date, bool $shouldBeMutable): Result
    {
        if ($shouldBeMutable) {
            return Result::success(
                $date instanceof DateTime ? $date : DateTime::createFromInterface($date)
            );
        }
        return Result::success(
            $date instanceof DateTimeImmutable ? $date : DateTimeImmutable::createFromInterface($date)
        );
    }

    private function shouldResultBeMutable(ParameterAttributeResolveContext $context): bool
    {
        $type = $context->getParameter()->getType();

        if ($type instanceof ReflectionNamedType && $type->getName() === DateTime::class) {
            return true;
        }

        if ($type instanceof ReflectionUnionType) {
            $hasMutable = false;
            /**
             * @psalm-suppress RedundantConditionGivenDocblockType Need for PHP 8.1
             */
            foreach ($type->getTypes() as $subType) {
                if ($subType instanceof ReflectionNamedType) {
                    switch ($subType->getName()) {
                        case DateTime::class:
                            $hasMutable = true;
                            break;
                        case DateTimeImmutable::class:
                        case DateTimeInterface::class:
                            return false;
                    }
                }
            }
            return $hasMutable;
        }

        return false;
    }
}
