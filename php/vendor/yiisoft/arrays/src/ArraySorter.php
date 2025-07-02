<?php

declare(strict_types=1);

namespace Yiisoft\Arrays;

use Closure;
use InvalidArgumentException;

use function array_fill;
use function array_multisort;
use function count;
use function is_array;
use function is_scalar;
use function range;

final class ArraySorter
{
    /**
     * Sorts an array of objects or arrays (with the same structure) by one or several keys.
     *
     * For example:
     *
     * ```php
     * $data = [
     *     ['age' => 30, 'name' => 'Alexander'],
     *     ['age' => 30, 'name' => 'Brian'],
     *     ['age' => 19, 'name' => 'Barney'],
     * ];
     * ArraySorter::multisort($data, ['age', 'name'], [SORT_ASC, SORT_DESC]);
     * ```
     *
     * After sorting we'll get the following in `$data`:
     *
     * ```php
     * [
     *     ['age' => 19, 'name' => 'Barney'],
     *     ['age' => 30, 'name' => 'Brian'],
     *     ['age' => 30, 'name' => 'Alexander'],
     * ];
     * ```
     *
     * @param array<array-key, array|object> $array The array to be sorted. The array will be modified after calling
     * this method.
     * @param array<array-key, Closure|string>|Closure|string $key The key(s) to be sorted by. This refers to a key
     * name of the sub-array elements, a property name of the objects, or an anonymous function returning the values
     * for comparison purpose. The anonymous function signature should be: `function($item)`.
     * To sort by multiple keys, provide an array of keys here.
     * @param array<array-key, int>|int $direction The sorting direction. It can be either `SORT_ASC` or `SORT_DESC`.
     * When sorting by multiple keys with different sorting directions, use an array of sorting directions.
     * @param array<array-key, int>|int $sortFlag The PHP sort flag. Valid values include
     * `SORT_REGULAR`, `SORT_NUMERIC`, `SORT_STRING`, `SORT_LOCALE_STRING`, `SORT_NATURAL` and `SORT_FLAG_CASE`.
     * Please refer to [PHP manual](https://php.net/manual/en/function.sort.php)
     * for more details. When sorting by multiple keys with different sort flags, use an array of sort flags.
     *
     * @throws InvalidArgumentException If the `$direction` or `$sortFlag` parameters do not have
     * correct number of elements as that of $key.`
     */
    public static function multisort(
        array &$array,
        array|Closure|string $key,
        array|int $direction = SORT_ASC,
        array|int $sortFlag = SORT_REGULAR
    ): void {
        $keys = self::getKeys($array, $key);
        if (empty($keys)) {
            return;
        }

        $n = count($keys);
        if (is_scalar($direction)) {
            $direction = array_fill(0, $n, $direction);
        } elseif (count($direction) !== $n) {
            throw new InvalidArgumentException('The length of $direction parameter must be the same as that of $keys.');
        }

        if (is_scalar($sortFlag)) {
            $sortFlag = array_fill(0, $n, $sortFlag);
        } elseif (count($sortFlag) !== $n) {
            throw new InvalidArgumentException('The length of $sortFlag parameter must be the same as that of $keys.');
        }

        $_args = self::getArguments($array, $keys, $direction, $sortFlag);

        /** @psalm-suppress UnsupportedReferenceUsage */
        $_args[] = &$array;

        /** @psalm-suppress MixedArgument */
        array_multisort(...$_args);
    }

    /**
     * Get keys for get arguments.
     *
     * @param array<array-key, array|object> $array The array to be sorted.
     * @param array<array-key, Closure|string>|Closure|string $key The keys to be sorted by. This refers to a key name
     * of the sub-array elements, a property name of the objects, or an anonymous function returning the values for
     * comparison purpose. The anonymous function signature should be: `function($item)`.
     * To sort by multiple keys, provide an array of keys here.
     *
     * @return array<array-key, Closure|string> The keys.
     */
    private static function getKeys(array $array, array|Closure|string $key): array
    {
        $keys = is_array($key) ? $key : [$key];
        if (empty($keys) || empty($array)) {
            return [];
        }

        return $keys;
    }

    /**
     * Get arguments for multisort.
     *
     * @param array<array-key, array|object> $array The array to be sorted.
     * @param array<array-key, Closure|string> $keys Array of keys.
     * @param array<array-key, int> $direction Array of sorting directions.
     * @param array<array-key, int> $sortFlags Array of sort flags.
     *
     * @return array The arguments.
     */
    private static function getArguments(array $array, array $keys, array $direction, array $sortFlags): array
    {
        $args = [];
        foreach ($keys as $i => $iKey) {
            $flag = $sortFlags[$i];
            $args[] = ArrayHelper::getColumn($array, $iKey);
            $args[] = $direction[$i];
            $args[] = $flag;
        }

        // This fix is used for cases when main sorting specified by columns has equal values.
        // Without it will lead to Fatal Error: Nesting level too deep - recursive dependency?
        $args[] = range(1, count($array));
        $args[] = SORT_ASC;
        $args[] = SORT_NUMERIC;

        return $args;
    }
}
