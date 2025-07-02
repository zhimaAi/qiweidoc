<?php

declare(strict_types=1);

namespace Yiisoft\Strings;

use InvalidArgumentException;
use Stringable;

use function filter_var;
use function fmod;
use function gettype;
use function in_array;
use function is_bool;
use function is_numeric;
use function is_scalar;
use function preg_replace;
use function str_replace;

/**
 * Provides static methods to work with numeric strings.
 */
final class NumericHelper
{
    /**
     * Converts number to its ordinal English form. For example, converts 13 to 13th, 2 to 2nd etc.
     *
     * @param float|int|string $value The number to get its ordinal value.
     */
    public static function toOrdinal(mixed $value): string
    {
        if (!is_numeric($value)) {
            $type = gettype($value);
            throw new InvalidArgumentException("Value must be numeric. $type given.");
        }

        if (fmod((float)$value, 1) !== 0.00) {
            return (string)$value;
        }

        if (in_array($value % 100, [11, 12, 13], true)) {
            return $value . 'th';
        }

        return match ($value % 10) {
            1 => $value . 'st',
            2 => $value . 'nd',
            3 => $value . 'rd',
            default => $value . 'th',
        };
    }

    /**
     * Returns string representation of a number value without thousands separators and with dot as decimal separator.
     *
     * @param bool|float|int|string|Stringable $value String in `string` or `Stringable` must be valid UTF-8 string.
     *
     * @throws InvalidArgumentException if value is not scalar.
     */
    public static function normalize(mixed $value): string
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (!is_scalar($value) && !$value instanceof Stringable) {
            $type = gettype($value);
            throw new InvalidArgumentException("Value must be scalar. $type given.");
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        $value = str_replace([' ', ','], ['', '.'], (string) $value);

        /**
         * @var string We assume that `$value` is valid UTF-8 string, so `preg_replace()` never returns `false`.
         */
        return preg_replace('/\.(?=.*\.)/', '', $value);
    }

    /**
     * Checks whether the given string is an integer number.
     *
     * Require Filter PHP extension ({@see https://www.php.net/manual/intro.filter.php}).
     */
    public static function isInteger(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
}
