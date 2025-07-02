<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Filter;

use Yiisoft\Data\Reader\FilterAssert;
use Yiisoft\Data\Reader\FilterInterface;

/**
 * `In` filter defines a criteria for ensuring field value matches one of the value provided.
 */
final class In implements FilterInterface
{
    /**
     * @var bool[]|float[]|int[]|string[] Values to check against.
     */
    private array $values;

    /**
     * @param string $field Name of the field to compare.
     * @param bool[]|float[]|int[]|string[] $values Values to check against.
     */
    public function __construct(private string $field, array $values)
    {
        foreach ($values as $value) {
            FilterAssert::isScalar($value);
        }
        $this->values = $values;
    }

    public function toCriteriaArray(): array
    {
        return [self::getOperator(), $this->field, $this->values];
    }

    public static function getOperator(): string
    {
        return 'in';
    }
}
