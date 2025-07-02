<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader;

use DateTimeInterface;
use InvalidArgumentException;
use Yiisoft\Data\Reader\Iterable\IterableFilterHandlerInterface;

use function is_scalar;
use function is_string;
use function sprintf;

/**
 * Filter-related assertions.
 */
final class FilterAssert
{
    /**
     * Asserts that field is a string.
     *
     * @param mixed $field Value to check.
     *
     * @throws InvalidArgumentException If value is not correct.
     */
    public static function fieldIsString(mixed $field): void
    {
        if (!is_string($field)) {
            throw new InvalidArgumentException(sprintf(
                'The field should be string. The %s is received.',
                get_debug_type($field),
            ));
        }
    }

    /**
     * Asserts that the value is an instance of {@see IterableFilterHandlerInterface}.
     *
     * @param mixed $value Value to check.
     *
     * @throws InvalidArgumentException If value is not correct.
     */
    public static function isIterableFilterHandlerInterface(mixed $value): void
    {
        if (!$value instanceof IterableFilterHandlerInterface) {
            throw new InvalidArgumentException(sprintf(
                'The filter handler should be an object and implement "%s". The %s is received.',
                IterableFilterHandlerInterface::class,
                get_debug_type($value),
            ));
        }
    }

    /**
     * Asset that value is scalar.
     *
     * @param mixed $value Value to check.
     *
     * @throws InvalidArgumentException If value is not correct.
     */
    public static function isScalar(mixed $value): void
    {
        if (!is_scalar($value)) {
            throw new InvalidArgumentException(sprintf(
                'The value should be scalar. The %s is received.',
                get_debug_type($value),
            ));
        }
    }

    /**
     * Asserts that value is either a scalar or an instance of {@see DateTimeInterface}.
     *
     * @param mixed $value Value to check.
     *
     * @throws InvalidArgumentException If value is not correct.
     */
    public static function isScalarOrInstanceOfDateTimeInterface(mixed $value): void
    {
        if (!$value instanceof DateTimeInterface && !is_scalar($value)) {
            throw new InvalidArgumentException(sprintf(
                'The value should be scalar or %s instance. The %s is received.',
                DateTimeInterface::class,
                get_debug_type($value),
            ));
        }
    }
}
