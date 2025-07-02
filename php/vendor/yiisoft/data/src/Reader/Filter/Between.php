<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Filter;

use DateTimeInterface;
use Yiisoft\Data\Reader\FilterAssert;
use Yiisoft\Data\Reader\FilterInterface;

/**
 * `Between` filter defines a criteria so the value of the field with a given name
 * is between the minimal value and the maximal value.
 */
final class Between implements FilterInterface
{
    private bool|DateTimeInterface|float|int|string $minValue;

    private bool|DateTimeInterface|float|int|string $maxValue;

    /**
     * @param string $field Name of the field to compare.
     * @param bool|DateTimeInterface|float|int|string $minValue Minimal field value.
     * @param bool|DateTimeInterface|float|int|string $maxValue Maximal field value.
     */
    public function __construct(private string $field, mixed $minValue, mixed $maxValue)
    {
        FilterAssert::isScalarOrInstanceOfDateTimeInterface($minValue);
        FilterAssert::isScalarOrInstanceOfDateTimeInterface($maxValue);

        $this->minValue = $minValue;
        $this->maxValue = $maxValue;
    }

    public function toCriteriaArray(): array
    {
        return [self::getOperator(), $this->field, $this->minValue, $this->maxValue];
    }

    public static function getOperator(): string
    {
        return 'between';
    }
}
