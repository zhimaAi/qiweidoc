<?php

declare(strict_types=1);

namespace Yiisoft\Arrays;

use Closure;
use InvalidArgumentException;
use Throwable;
use Yiisoft\Strings\NumericHelper;
use Yiisoft\Strings\StringHelper;

use function array_column;
use function array_combine;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_pop;
use function array_search;
use function array_shift;
use function count;
use function end;
use function explode;
use function get_object_vars;
use function gettype;
use function htmlspecialchars;
use function htmlspecialchars_decode;
use function in_array;
use function is_array;
use function is_float;
use function is_int;
use function is_object;
use function is_string;
use function range;
use function str_ends_with;
use function strcasecmp;
use function substr;

/**
 * Yii array helper provides static methods allowing you to deal with arrays more efficiently.
 *
 * @psalm-type ArrayKey = float|int|string|array<array-key,float|int|string>
 * @psalm-type ArrayPath = float|int|string|array<array-key,float|int|string|array<array-key,float|int|string>>
 */
final class ArrayHelper
{
    /**
     * Converts an object or an array of objects into an array.
     *
     * For example:
     *
     * ```php
     * [
     *     Post::class => [
     *         'id',
     *         'title',
     *         'createTime' => 'created_at',
     *         'length' => function ($post) {
     *             return strlen($post->content);
     *         },
     *     ],
     * ]
     * ```
     *
     * The result of `ArrayHelper::toArray($post, $properties)` could be like the following:
     *
     * ```php
     * [
     *     'id' => 123,
     *     'title' => 'test',
     *     'createTime' => '2013-01-01 12:00AM',
     *     'length' => 301,
     * ]
     * ```
     *
     * @param mixed $object The object to be converted into an array.
     *
     * It is possible to provide default way of converting object to array for a specific class by implementing
     * {@see \Yiisoft\Arrays\ArrayableInterface} in its class.
     * @param array $properties A mapping from object class names to the properties that need to put into
     * the resulting arrays. The properties specified for each class is an array of the following format:
     *
     * - A field name to include as is.
     * - A key-value pair of desired array key name and model column name to take value from.
     * - A key-value pair of desired array key name and a callback which returns value.
     * @param bool $recursive Whether to recursively converts properties which are objects into arrays.
     *
     * @return array The array representation of the object.
     */
    public static function toArray(mixed $object, array $properties = [], bool $recursive = true): array
    {
        if (is_array($object)) {
            if ($recursive) {
                foreach ($object as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        $object[$key] = self::toArray($value, $properties);
                    }
                }
            }

            return $object;
        }

        if (is_object($object)) {
            if (!empty($properties)) {
                $className = $object::class;
                if (!empty($properties[$className])) {
                    $result = [];
                    /**
                     * @var int|string $key
                     * @var string $name
                     */
                    foreach ($properties[$className] as $key => $name) {
                        if (is_int($key)) {
                            $result[$name] = $object->$name;
                        } else {
                            $result[$key] = self::getValue($object, $name);
                        }
                    }

                    return $recursive ? self::toArray($result, $properties) : $result;
                }
            }
            if ($object instanceof ArrayableInterface) {
                $result = $object->toArray([], [], $recursive);
            } else {
                $result = [];
                /**
                 * @var string $key
                 */
                foreach ($object as $key => $value) {
                    $result[$key] = $value;
                }
            }

            return $recursive ? self::toArray($result, $properties) : $result;
        }

        return [$object];
    }

    /**
     * Merges two or more arrays into one recursively. If each array has an element with the same string key value,
     * the latter will overwrite the former (different from {@see array_merge_recursive()}). Recursive merging will be
     * conducted if both arrays have an element of array type and are having the same key. For integer-keyed elements,
     * the elements from the latter array will be appended to the former array.
     *
     * @param array ...$arrays Arrays to be merged.
     *
     * @return array The merged array (the original arrays are not changed).
     */
    public static function merge(...$arrays): array
    {
        return self::doMerge($arrays, null);
    }

    /**
     * Merges two or more arrays into one recursively with specified depth. If each array has an element with the same
     * string key value, the latter will overwrite the former (different from {@see array_merge_recursive()}).
     * Recursive merging will be conducted if both arrays have an element of array type and are having the same key.
     * For integer-keyed elements, the elements from the latter array will be appended to the former array.
     *
     * @param array[] $arrays Arrays to be merged.
     * @param int|null $depth The maximum depth that merging is recursively. `Null` means unlimited depth.
     *
     * @return array The merged array (the original arrays are not changed).
     */
    public static function parametrizedMerge(array $arrays, ?int $depth): array
    {
        return self::doMerge($arrays, $depth);
    }

    /**
     * Retrieves the value of an array element or object property with the given key or property name.
     * If the key does not exist in the array or object, the default value will be returned instead.
     *
     * Below are some usage examples,
     *
     * ```php
     * // Working with array:
     * $username = \Yiisoft\Arrays\ArrayHelper::getValue($_POST, 'username');
     *
     * // Working with object:
     * $username = \Yiisoft\Arrays\ArrayHelper::getValue($user, 'username');
     *
     * // Working with anonymous function:
     * $fullName = \Yiisoft\Arrays\ArrayHelper::getValue($user, function ($user, $defaultValue) {
     *     return $user->firstName . ' ' . $user->lastName;
     * });
     *
     * // Using an array of keys to retrieve the value:
     * $value = \Yiisoft\Arrays\ArrayHelper::getValue($versions, ['1.0', 'date']);
     * ```
     *
     * @param array|object $array Array or object to extract value from.
     * @param array|Closure|float|int|string $key Key name of the array element,
     * an array of keys, object property name, object method like `getName()`, or an anonymous function
     * returning the value. The anonymous function signature should be:
     * `function($array, $defaultValue)`.
     * @param mixed $default The default value to be returned if the specified array key does not exist. Not used when
     * getting value from an object.
     *
     * @psalm-param ArrayKey|Closure $key
     *
     * @return mixed The value of the element if found, default value otherwise.
     */
    public static function getValue(
        array|object $array,
        array|Closure|float|int|string $key,
        mixed $default = null
    ): mixed {
        if ($key instanceof Closure) {
            return $key($array, $default);
        }

        if (is_array($key)) {
            if (empty($key)) {
                return $default;
            }
            $lastKey = array_pop($key);
            foreach ($key as $keyPart) {
                $array = self::getRootValue($array, $keyPart, null);
                if (!is_array($array) && !is_object($array)) {
                    return $default;
                }
            }
            return self::getRootValue($array, $lastKey, $default);
        }

        return self::getRootValue($array, $key, $default);
    }

    /**
     * @param mixed $array Array or object to extract value from, otherwise method will return $default.
     * @param float|int|string $key Key name of the array element, object property name or object method like `getValue()`.
     * @param mixed $default The default value to be returned if the specified array key does not exist. Not used when
     * getting value from an object.
     *
     * @return mixed The value of the element if found, default value otherwise.
     */
    private static function getRootValue(mixed $array, float|int|string $key, mixed $default): mixed
    {
        if (is_array($array)) {
            $key = self::normalizeArrayKey($key);
            return array_key_exists($key, $array) ? $array[$key] : $default;
        }

        if (is_object($array)) {
            $key = (string) $key;

            if (str_ends_with($key, '()')) {
                $method = substr($key, 0, -2);
                /** @psalm-suppress MixedMethodCall */
                return $array->$method();
            }

            try {
                /** @psalm-suppress MixedPropertyFetch */
                return $array::$$key;
            } catch (Throwable) {
                /**
                 * This is expected to fail if the property does not exist, or __get() is not implemented.
                 * It is not reliably possible to check whether a property is accessible beforehand.
                 *
                 * @psalm-suppress MixedPropertyFetch
                 */
                return $array->$key;
            }
        }

        return $default;
    }

    /**
     * Retrieves the value of an array element or object property with the given key or property name.
     * If the key does not exist in the array or object, the default value will be returned instead.
     *
     * The key may be specified in a dot-separated format to retrieve the value of a sub-array or the property
     * of an embedded object. In particular, if the key is `x.y.z`, then the returned value would
     * be `$array['x']['y']['z']` or `$array->x->y->z` (if `$array` is an object). If `$array['x']`
     * or `$array->x` is neither an array nor an object, the default value will be returned.
     * Note that if the array already has an element `x.y.z`, then its value will be returned
     * instead of going through the sub-arrays. So it is better to be done specifying an array of key names
     * like `['x', 'y', 'z']`.
     *
     * Below are some usage examples,
     *
     * ```php
     * // Using separated format to retrieve the property of embedded object:
     * $street = \Yiisoft\Arrays\ArrayHelper::getValue($users, 'address.street');
     *
     * // Using an array of keys to retrieve the value:
     * $value = \Yiisoft\Arrays\ArrayHelper::getValue($versions, ['1.0', 'date']);
     * ```
     *
     * @param array|object $array Array or object to extract value from.
     * @param array|Closure|float|int|string $path Key name of the array element, an array of keys or property name
     * of the object, or an anonymous function returning the value. The anonymous function signature should be:
     * `function($array, $defaultValue)`.
     * @param mixed $default The default value to be returned if the specified array key does not exist. Not used when
     * getting value from an object.
     * @param string $delimiter A separator, used to parse string $key for embedded object property retrieving. Defaults
     * to "." (dot).
     *
     * @psalm-param ArrayPath|Closure $path
     *
     * @return mixed The value of the element if found, default value otherwise.
     */
    public static function getValueByPath(
        array|object $array,
        array|Closure|float|int|string $path,
        mixed $default = null,
        string $delimiter = '.'
    ): mixed {
        return self::getValue(
            $array,
            $path instanceof Closure ? $path : self::parseMixedPath($path, $delimiter),
            $default
        );
    }

    /**
     * Writes a value into an associative array at the key path specified.
     * If there is no such key path yet, it will be created recursively.
     * If the key exists, it will be overwritten.
     *
     * ```php
     *  $array = [
     *      'key' => [
     *          'in' => [
     *              'val1',
     *              'key' => 'val'
     *          ]
     *      ]
     *  ];
     * ```
     *
     * The result of `ArrayHelper::setValue($array, ['key', 'in'], ['arr' => 'val']);`
     * will be the following:
     *
     * ```php
     *  [
     *      'key' => [
     *          'in' => [
     *              'arr' => 'val'
     *          ]
     *      ]
     *  ]
     * ```
     *
     * @param array $array The array to write the value to.
     * @param array|float|int|string|null $key The path of where do you want to write a value to `$array`
     * the path can be described by an array of keys. If the path is null then `$array` will be assigned the `$value`.
     *
     * @psalm-param ArrayKey|null $key
     *
     * @param mixed $value The value to be written.
     */
    public static function setValue(array &$array, array|float|int|string|null $key, mixed $value): void
    {
        if ($key === null) {
            $array = $value;
            return;
        }

        $keys = is_array($key) ? $key : [$key];

        while (count($keys) > 1) {
            $k = self::normalizeArrayKey(array_shift($keys));
            if (!isset($array[$k])) {
                $array[$k] = [];
            }
            if (!is_array($array[$k])) {
                $array[$k] = [$array[$k]];
            }
            $array = &$array[$k];
            /** @var array $array */
        }

        $array[self::normalizeArrayKey(array_shift($keys))] = $value;
    }

    /**
     * Find array value in array at the key path specified and add passed value to him.
     *
     * If there is no such key path yet, it will be created recursively and an empty array will be initialized.
     *
     * ```php
     * $array = ['key' => []];
     *
     * ArrayHelper::addValue($array, ['key', 'in'], 'variable1');
     * ArrayHelper::addValue($array, ['key', 'in'], 'variable2');
     *
     * // Result: ['key' => ['in' => ['variable1', 'variable2']]]
     * ```
     *
     * If the value exists, it will become the first element of the array.
     *
     * ```php
     * $array = ['key' => 'in'];
     *
     * ArrayHelper::addValue($array, ['key'], 'variable1');
     *
     * // Result: ['key' => ['in', 'variable1']]
     * ```
     *
     * @param array $array The array to append the value to.
     * @param array|float|int|string|null $key The path of where do you want to append a value to `$array`. The path can
     * be described by an array of keys. If the path is null then `$value` will be appended to the `$array`.
     *
     * @psalm-param ArrayKey|null $key
     *
     * @param mixed $value The value to be appended.
     */
    public static function addValue(array &$array, array|float|int|string|null $key, mixed $value): void
    {
        if ($key === null) {
            $array[] = $value;
            return;
        }

        $keys = is_array($key) ? $key : [$key];

        while (count($keys) > 0) {
            $k = self::normalizeArrayKey(array_shift($keys));

            if (!array_key_exists($k, $array)) {
                $array[$k] = [];
            } elseif (!is_array($array[$k])) {
                $array[$k] = [$array[$k]];
            }

            $array = &$array[$k];
            /** @var array $array */
        }

        $array[] = $value;
    }

    /**
     * Find array value in array at the key path specified and add passed value to him.
     *
     * @see addValue
     *
     * @param array $array The array to append the value to.
     * @param array|float|int|string|null $path The path of where do you want to append a value to `$array`. The path
     * can be described by a string when each key should be separated by a dot. You can also describe the path as
     * an array of keys. If the path is null then `$value` will be appended to the `$array`.
     * @param mixed $value The value to be added.
     * @param string $delimiter A separator, used to parse string $key for embedded object property retrieving. Defaults
     * to "." (dot).
     *
     * @psalm-param ArrayPath|null $path
     */
    public static function addValueByPath(
        array &$array,
        array|float|int|string|null $path,
        mixed $value,
        string $delimiter = '.'
    ): void {
        self::addValue($array, $path === null ? null : self::parseMixedPath($path, $delimiter), $value);
    }

    /**
     * Writes a value into an associative array at the key path specified.
     * If there is no such key path yet, it will be created recursively.
     * If the key exists, it will be overwritten.
     *
     * ```php
     *  $array = [
     *      'key' => [
     *          'in' => [
     *              'val1',
     *              'key' => 'val'
     *          ]
     *      ]
     *  ];
     * ```
     *
     * The result of `ArrayHelper::setValue($array, 'key.in.0', ['arr' => 'val']);` will be the following:
     *
     * ```php
     *  [
     *      'key' => [
     *          'in' => [
     *              ['arr' => 'val'],
     *              'key' => 'val'
     *          ]
     *      ]
     *  ]
     *
     * ```
     *
     * The result of
     * `ArrayHelper::setValue($array, 'key.in', ['arr' => 'val']);` or
     * `ArrayHelper::setValue($array, ['key', 'in'], ['arr' => 'val']);`
     * will be the following:
     *
     * ```php
     *  [
     *      'key' => [
     *          'in' => [
     *              'arr' => 'val'
     *          ]
     *      ]
     *  ]
     * ```
     *
     * @param array $array The array to write the value to.
     * @param array|float|int|string|null $path The path of where do you want to write a value to `$array`.
     * The path can be described by a string when each key should be separated by a dot.
     * You can also describe the path as an array of keys. If the path is null then `$array` will be assigned
     * the `$value`.
     * @param mixed $value The value to be written.
     * @param string $delimiter A separator, used to parse string $key for embedded object property retrieving. Defaults
     * to "." (dot).
     *
     * @psalm-param ArrayPath|null $path
     */
    public static function setValueByPath(
        array &$array,
        array|float|int|string|null $path,
        mixed $value,
        string $delimiter = '.'
    ): void {
        self::setValue($array, $path === null ? null : self::parseMixedPath($path, $delimiter), $value);
    }

    /**
     * Removes an item from an array and returns the value. If the key does not exist in the array, the default value
     * will be returned instead.
     *
     * Usage examples,
     *
     * ```php
     * // $array = ['type' => 'A', 'options' => [1, 2]];
     *
     * // Working with array:
     * $type = \Yiisoft\Arrays\ArrayHelper::remove($array, 'type');
     *
     * // $array content
     * // $array = ['options' => [1, 2]];
     * ```
     *
     * @param array $array The array to extract value from.
     * @param array|float|int|string $key Key name of the array element or associative array at the key path specified.
     * @param mixed $default The default value to be returned if the specified key does not exist.
     *
     * @psalm-param ArrayKey $key
     *
     * @return mixed The value of the element if found, default value otherwise.
     */
    public static function remove(array &$array, array|float|int|string $key, mixed $default = null): mixed
    {
        $keys = is_array($key) ? $key : [$key];

        while (count($keys) > 1) {
            $key = self::normalizeArrayKey(array_shift($keys));
            if (!isset($array[$key]) || !is_array($array[$key])) {
                return $default;
            }
            $array = &$array[$key];
        }

        $key = self::normalizeArrayKey(array_shift($keys));
        if (array_key_exists($key, $array)) {
            $value = $array[$key];
            unset($array[$key]);
            return $value;
        }

        return $default;
    }

    /**
     * Removes an item from an array and returns the value. If the key does not exist in the array, the default value
     * will be returned instead.
     *
     * Usage examples,
     *
     * ```php
     * // $array = ['type' => 'A', 'options' => [1, 2]];
     *
     * // Working with array:
     * $type = \Yiisoft\Arrays\ArrayHelper::remove($array, 'type');
     *
     * // $array content
     * // $array = ['options' => [1, 2]];
     * ```
     *
     * @param array $array The array to extract value from.
     * @param array|float|int|string $path Key name of the array element or associative array at the key path specified.
     * The path can be described by a string when each key should be separated by a delimiter (default is dot).
     * @param mixed $default The default value to be returned if the specified key does not exist.
     * @param string $delimiter A separator, used to parse string $key for embedded object property retrieving. Defaults
     * to "." (dot).
     *
     * @psalm-param ArrayPath $path
     *
     * @return mixed The value of the element if found, default value otherwise.
     */
    public static function removeByPath(
        array &$array,
        array|float|int|string $path,
        mixed $default = null,
        string $delimiter = '.'
    ): mixed {
        return self::remove($array, self::parseMixedPath($path, $delimiter), $default);
    }

    /**
     * Removes items with matching values from the array and returns the removed items.
     *
     * Example,
     *
     * ```php
     * $array = ['Bob' => 'Dylan', 'Michael' => 'Jackson', 'Mick' => 'Jagger', 'Janet' => 'Jackson'];
     * $removed = \Yiisoft\Arrays\ArrayHelper::removeValue($array, 'Jackson');
     * // result:
     * // $array = ['Bob' => 'Dylan', 'Mick' => 'Jagger'];
     * // $removed = ['Michael' => 'Jackson', 'Janet' => 'Jackson'];
     * ```
     *
     * @param array $array The array where to look the value from.
     * @param mixed $value The value to remove from the array.
     *
     * @return array The items that were removed from the array.
     */
    public static function removeValue(array &$array, mixed $value): array
    {
        $result = [];
        foreach ($array as $key => $val) {
            if ($val === $value) {
                $result[$key] = $val;
                unset($array[$key]);
            }
        }

        return $result;
    }

    /**
     * Indexes and/or groups the array according to a specified key.
     * The input should be either multidimensional array or an array of objects.
     *
     * The `$key` can be either a key name of the sub-array, a property name of object, or an anonymous
     * function that must return the value that will be used as a key.
     *
     * `$groups` is an array of keys, that will be used to group the input array into one or more sub-arrays based
     * on keys specified.
     *
     * If the `$key` is specified as `null` or a value of an element corresponding to the key is `null` in addition
     * to `$groups` not specified then the element is discarded.
     *
     * For example:
     *
     * ```php
     * $array = [
     *     ['id' => '123', 'data' => 'abc', 'device' => 'laptop'],
     *     ['id' => '345', 'data' => 'def', 'device' => 'tablet'],
     *     ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone'],
     * ];
     * $result = ArrayHelper::index($array, 'id');
     * ```
     *
     * The result will be an associative array, where the key is the value of `id` attribute
     *
     * ```php
     * [
     *     '123' => ['id' => '123', 'data' => 'abc', 'device' => 'laptop'],
     *     '345' => ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone']
     *     // The second element of an original array is overwritten by the last element because of the same id
     * ]
     * ```
     *
     * An anonymous function can be used in the grouping array as well.
     *
     * ```php
     * $result = ArrayHelper::index($array, function ($element) {
     *     return $element['id'];
     * });
     * ```
     *
     * Passing `id` as a third argument will group `$array` by `id`:
     *
     * ```php
     * $result = ArrayHelper::index($array, null, 'id');
     * ```
     *
     * The result will be a multidimensional array grouped by `id` on the first level, by `device` on the second level
     * and indexed by `data` on the third level:
     *
     * ```php
     * [
     *     '123' => [
     *         ['id' => '123', 'data' => 'abc', 'device' => 'laptop']
     *     ],
     *     '345' => [ // all elements with this index are present in the result array
     *         ['id' => '345', 'data' => 'def', 'device' => 'tablet'],
     *         ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone'],
     *     ]
     * ]
     * ```
     *
     * The anonymous function can be used in the array of grouping keys as well:
     *
     * ```php
     * $result = ArrayHelper::index($array, 'data', [function ($element) {
     *     return $element['id'];
     * }, 'device']);
     * ```
     *
     * The result will be a multidimensional array grouped by `id` on the first level, by the `device` on the second one
     * and indexed by the `data` on the third level:
     *
     * ```php
     * [
     *     '123' => [
     *         'laptop' => [
     *             'abc' => ['id' => '123', 'data' => 'abc', 'device' => 'laptop']
     *         ]
     *     ],
     *     '345' => [
     *         'tablet' => [
     *             'def' => ['id' => '345', 'data' => 'def', 'device' => 'tablet']
     *         ],
     *         'smartphone' => [
     *             'hgi' => ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone']
     *         ]
     *     ]
     * ]
     * ```
     *
     * @param iterable $array The array or iterable object that needs to be indexed or grouped.
     * @param Closure|string|null $key The column name or anonymous function which result will be used
     * to index the array.
     * @param Closure[]|string|string[]|null $groups The array of keys, that will be used to group the input
     * array by one or more keys. If the `$key` attribute or its value for the particular element is null and `$groups`
     * is not defined, the array element will be discarded. Otherwise, if `$groups` is specified, array element will be
     * added to the result array without any key.
     *
     * @psalm-param iterable<mixed, array|object> $array
     *
     * @return array The indexed and/or grouped array.
     */
    public static function index(
        iterable $array,
        Closure|string|null $key,
        array|string|null $groups = []
    ): array {
        $result = [];
        $groups = (array)$groups;

        /** @var mixed $element */
        foreach ($array as $element) {
            if (!is_array($element) && !is_object($element)) {
                throw new InvalidArgumentException(
                    'index() can not get value from ' . gettype($element) .
                    '. The $array should be either multidimensional array or an array of objects.'
                );
            }

            $lastArray = &$result;

            foreach ($groups as $group) {
                $value = self::normalizeArrayKey(
                    self::getValue($element, $group)
                );
                if (!array_key_exists($value, $lastArray)) {
                    $lastArray[$value] = [];
                }
                /** @psalm-suppress MixedAssignment */
                $lastArray = &$lastArray[$value];
                /** @var array $lastArray */
            }

            if ($key === null) {
                if (!empty($groups)) {
                    $lastArray[] = $element;
                }
            } else {
                $value = self::getValue($element, $key);
                if ($value !== null) {
                    $lastArray[self::normalizeArrayKey($value)] = $element;
                }
            }
            unset($lastArray);
        }

        return $result;
    }

    /**
     * Groups the array according to a specified key.
     * This is just an alias for indexing by groups
     *
     * @param iterable $array The array or iterable object that needs to be grouped.
     * @param Closure[]|string|string[] $groups The array of keys, that will be used to group the input array
     * by one or more keys.
     *
     * @psalm-param iterable<mixed, array|object> $array
     *
     * @return array The grouped array.
     */
    public static function group(iterable $array, array|string $groups): array
    {
        return self::index($array, null, $groups);
    }

    /**
     * Returns the values of a specified column in an array.
     * The input array should be multidimensional or an array of objects.
     *
     * For example,
     *
     * ```php
     * $array = [
     *     ['id' => '123', 'data' => 'abc'],
     *     ['id' => '345', 'data' => 'def'],
     * ];
     * $result = ArrayHelper::getColumn($array, 'id');
     * // the result is: ['123', '345']
     *
     * // using anonymous function
     * $result = ArrayHelper::getColumn($array, function ($element) {
     *     return $element['id'];
     * });
     * ```
     *
     * @param iterable $array The array or iterable object to get column from.
     * @param Closure|string $name Column name or a closure returning column name.
     * @param bool $keepKeys Whether to maintain the array keys. If false, the resulting array
     * will be re-indexed with integers.
     *
     * @psalm-param iterable<array-key, array|object> $array
     *
     * @return array The list of column values.
     */
    public static function getColumn(iterable $array, Closure|string $name, bool $keepKeys = true): array
    {
        $result = [];
        if ($keepKeys) {
            foreach ($array as $k => $element) {
                $result[$k] = self::getValue($element, $name);
            }
        } else {
            foreach ($array as $element) {
                $result[] = self::getValue($element, $name);
            }
        }

        return $result;
    }

    /**
     * Builds a map (key-value pairs) from a multidimensional array or an array of objects.
     * The `$from` and `$to` parameters specify the key names or property names to set up the map.
     * Optionally, one can further group the map according to a grouping field `$group`.
     *
     * For example,
     *
     * ```php
     * $array = [
     *     ['id' => '123', 'name' => 'aaa', 'class' => 'x'],
     *     ['id' => '124', 'name' => 'bbb', 'class' => 'x'],
     *     ['id' => '345', 'name' => 'ccc', 'class' => 'y'],
     * ];
     *
     * $result = ArrayHelper::map($array, 'id', 'name');
     * // the result is:
     * // [
     * //     '123' => 'aaa',
     * //     '124' => 'bbb',
     * //     '345' => 'ccc',
     * // ]
     *
     * $result = ArrayHelper::map($array, 'id', 'name', 'class');
     * // the result is:
     * // [
     * //     'x' => [
     * //         '123' => 'aaa',
     * //         '124' => 'bbb',
     * //     ],
     * //     'y' => [
     * //         '345' => 'ccc',
     * //     ],
     * // ]
     * ```
     *
     * @param iterable $array Array or iterable object to build map from.
     * @param Closure|string $from Key or property name to map from.
     * @param Closure|string $to Key or property name to map to.
     * @param Closure|string|null $group Key or property to group the map.
     *
     * @psalm-param iterable<mixed, array|object> $array
     *
     * @return array Resulting map.
     */
    public static function map(
        iterable $array,
        Closure|string $from,
        Closure|string $to,
        Closure|string|null $group = null
    ): array {
        if ($group === null) {
            if ($from instanceof Closure || $to instanceof Closure || !is_array($array)) {
                $result = [];
                foreach ($array as $element) {
                    $key = (string)self::getValue($element, $from);
                    $result[$key] = self::getValue($element, $to);
                }

                return $result;
            }

            return array_column($array, $to, $from);
        }

        $result = [];
        foreach ($array as $element) {
            $groupKey = (string)self::getValue($element, $group);
            $key = (string)self::getValue($element, $from);
            $result[$groupKey][$key] = self::getValue($element, $to);
        }

        return $result;
    }

    /**
     * Checks if the given array contains the specified key.
     * This method enhances the `array_key_exists()` function by supporting case-insensitive
     * key comparison.
     *
     * @param array $array The array with keys to check.
     * @param array|float|int|string $key The key to check.
     * @param bool $caseSensitive Whether the key comparison should be case-sensitive.
     *
     * @psalm-param ArrayKey $key
     *
     * @return bool Whether the array contains the specified key.
     */
    public static function keyExists(array $array, array|float|int|string $key, bool $caseSensitive = true): bool
    {
        if (is_array($key)) {
            if (empty($key)) {
                return false;
            }

            if (count($key) === 1) {
                return self::rootKeyExists($array, end($key), $caseSensitive);
            }

            /** @psalm-var non-empty-array<array-key,float|int|string> $key */

            foreach (self::getExistsKeys($array, array_shift($key), $caseSensitive) as $existKey) {
                $array = self::getRootValue($array, $existKey, null);
                if (is_array($array) && self::keyExists($array, $key, $caseSensitive)) {
                    return true;
                }
            }

            return false;
        }

        return self::rootKeyExists($array, $key, $caseSensitive);
    }

    private static function rootKeyExists(array $array, float|int|string $key, bool $caseSensitive): bool
    {
        $key = (string)$key;

        if ($caseSensitive) {
            return array_key_exists($key, $array);
        }

        foreach (array_keys($array) as $k) {
            if (strcasecmp($key, (string)$k) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array-key>
     */
    private static function getExistsKeys(array $array, float|int|string $key, bool $caseSensitive): array
    {
        $key = (string)$key;

        if ($caseSensitive) {
            return [$key];
        }

        return array_filter(
            array_keys($array),
            static fn ($k) => strcasecmp($key, (string)$k) === 0
        );
    }

    /**
     * Checks if the given array contains the specified key. The key may be specified in a dot format.
     * In particular, if the key is `x.y.z`, then key would be `$array['x']['y']['z']`.
     *
     * This method enhances the `array_key_exists()` function by supporting case-insensitive
     * key comparison.
     *
     * @param array $array The array to check path in.
     * @param array|float|int|string $path The key path. Can be described by a string when each key should be separated
     * by delimiter. You can also describe the path as an array of keys.
     * @param bool $caseSensitive Whether the key comparison should be case-sensitive.
     * @param string $delimiter A separator, used to parse string $key for embedded object property retrieving. Defaults
     * to "." (dot).
     *
     * @psalm-param ArrayPath $path
     */
    public static function pathExists(
        array $array,
        array|float|int|string $path,
        bool $caseSensitive = true,
        string $delimiter = '.'
    ): bool {
        return self::keyExists($array, self::parseMixedPath($path, $delimiter), $caseSensitive);
    }

    /**
     * Encodes special characters in an array of strings into HTML entities.
     * Only array values will be encoded by default.
     * If a value is an array, this method will also encode it recursively.
     * Only string values will be encoded.
     *
     * @param iterable $data Data to be encoded.
     * @param bool $valuesOnly Whether to encode array values only. If false,
     * both the array keys and array values will be encoded.
     * @param string|null $encoding The encoding to use, defaults to `ini_get('default_charset')`.
     *
     * @psalm-param iterable<mixed, mixed> $data
     *
     * @return array The encoded data.
     *
     * @link https://www.php.net/manual/en/function.htmlspecialchars.php
     */
    public static function htmlEncode(iterable $data, bool $valuesOnly = true, ?string $encoding = null): array
    {
        $d = [];
        foreach ($data as $key => $value) {
            if (!is_int($key)) {
                $key = (string)$key;
            }
            if (!$valuesOnly && is_string($key)) {
                $key = htmlspecialchars($key, ENT_QUOTES | ENT_SUBSTITUTE, $encoding, true);
            }
            if (is_string($value)) {
                $d[$key] = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, $encoding, true);
            } elseif (is_array($value)) {
                $d[$key] = self::htmlEncode($value, $valuesOnly, $encoding);
            } else {
                $d[$key] = $value;
            }
        }

        return $d;
    }

    /**
     * Decodes HTML entities into the corresponding characters in an array of strings.
     * Only array values will be decoded by default.
     * If a value is an array, this method will also decode it recursively.
     * Only string values will be decoded.
     *
     * @param iterable $data Data to be decoded.
     * @param bool $valuesOnly Whether to decode array values only. If false,
     * both the array keys and array values will be decoded.
     *
     * @psalm-param iterable<mixed, mixed> $data
     *
     * @return array The decoded data.
     *
     * @link https://www.php.net/manual/en/function.htmlspecialchars-decode.php
     */
    public static function htmlDecode(iterable $data, bool $valuesOnly = true): array
    {
        $decoded = [];
        foreach ($data as $key => $value) {
            if (!is_int($key)) {
                $key = (string)$key;
            }
            if (!$valuesOnly && is_string($key)) {
                $key = htmlspecialchars_decode($key, ENT_QUOTES);
            }
            if (is_string($value)) {
                $decoded[$key] = htmlspecialchars_decode($value, ENT_QUOTES);
            } elseif (is_array($value)) {
                $decoded[$key] = self::htmlDecode($value);
            } else {
                $decoded[$key] = $value;
            }
        }

        return $decoded;
    }

    /**
     * Returns a value indicating whether the given array is an associative array.
     *
     * An array is associative if all its keys are strings. If `$allStrings` is false,
     * then an array will be treated as associative if at least one of its keys is a string.
     *
     * Note that an empty array will NOT be considered associative.
     *
     * @param array $array The array being checked.
     * @param bool $allStrings Whether the array keys must be all strings in order for
     * the array to be treated as associative.
     *
     * @return bool Whether the array is associative.
     */
    public static function isAssociative(array $array, bool $allStrings = true): bool
    {
        if ($array === []) {
            return false;
        }

        if ($allStrings) {
            foreach ($array as $key => $_value) {
                if (!is_string($key)) {
                    return false;
                }
            }

            return true;
        }

        foreach ($array as $key => $_value) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a value indicating whether the given array is an indexed array.
     *
     * An array is indexed if all its keys are integers. If `$consecutive` is true,
     * then the array keys must be a consecutive sequence starting from 0.
     *
     * Note that an empty array will be considered indexed.
     *
     * @param array $array The array being checked.
     * @param bool $consecutive Whether the array keys must be a consecutive sequence
     * in order for the array to be treated as indexed.
     *
     * @return bool Whether the array is indexed.
     */
    public static function isIndexed(array $array, bool $consecutive = false): bool
    {
        if ($array === []) {
            return true;
        }

        if ($consecutive) {
            return array_keys($array) === range(0, count($array) - 1);
        }

        foreach ($array as $key => $_value) {
            if (!is_int($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check whether an array or `\Traversable` contains an element.
     *
     * This method does the same as the PHP function {@see in_array()}
     * but additionally works for objects that implement the {@see \Traversable} interface.
     *
     * @param mixed $needle The value to look for.
     * @param iterable $haystack The set of values to search.
     * @param bool $strict Whether to enable strict (`===`) comparison.
     *
     * @throws InvalidArgumentException if `$haystack` is neither traversable nor an array.
     *
     * @return bool `true` if `$needle` was found in `$haystack`, `false` otherwise.
     *
     * @link https://php.net/manual/en/function.in-array.php
     */
    public static function isIn(mixed $needle, iterable $haystack, bool $strict = false): bool
    {
        if (is_array($haystack)) {
            return in_array($needle, $haystack, $strict);
        }

        foreach ($haystack as $value) {
            if ($needle == $value && (!$strict || $needle === $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether an array or {@see \Traversable} is a subset of another array or {@see \Traversable}.
     *
     * This method will return `true`, if all elements of `$needles` are contained in
     * `$haystack`. If at least one element is missing, `false` will be returned.
     *
     * @param iterable $needles The values that must **all** be in `$haystack`.
     * @param iterable $haystack The set of value to search.
     * @param bool $strict Whether to enable strict (`===`) comparison.
     *
     * @throws InvalidArgumentException if `$haystack` or `$needles` is neither traversable nor an array.
     *
     * @return bool `true` if `$needles` is a subset of `$haystack`, `false` otherwise.
     */
    public static function isSubset(iterable $needles, iterable $haystack, bool $strict = false): bool
    {
        foreach ($needles as $needle) {
            if (!self::isIn($needle, $haystack, $strict)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Filters array according to rules specified.
     *
     * For example:
     *
     * ```php
     * $array = [
     *     'A' => [1, 2],
     *     'B' => [
     *         'C' => 1,
     *         'D' => 2,
     *     ],
     *     'E' => 1,
     * ];
     *
     * $result = \Yiisoft\Arrays\ArrayHelper::filter($array, ['A']);
     * // $result will be:
     * // [
     * //     'A' => [1, 2],
     * // ]
     *
     * $result = \Yiisoft\Arrays\ArrayHelper::filter($array, ['A', 'B.C']);
     * // $result will be:
     * // [
     * //     'A' => [1, 2],
     * //     'B' => ['C' => 1],
     * // ]
     *
     * $result = \Yiisoft\Arrays\ArrayHelper::filter($array, ['B', '!B.C']);
     * // $result will be:
     * // [
     * //     'B' => ['D' => 2],
     * // ]
     * ```
     *
     * @param array $array Source array.
     * @param list<string> $filters Rules that define array keys which should be left or removed from results.
     * Each rule is:
     * - `var` - `$array['var']` will be left in result.
     * - `var.key` = only `$array['var']['key']` will be left in result.
     * - `!var.key` = `$array['var']['key']` will be removed from result.
     *
     * @return array Filtered array.
     */
    public static function filter(array $array, array $filters): array
    {
        $result = [];
        $excludeFilters = [];

        foreach ($filters as $filter) {
            if ($filter[0] === '!') {
                $excludeFilters[] = substr($filter, 1);
                continue;
            }

            $nodeValue = $array; // Set $array as root node.
            $keys = explode('.', $filter);
            foreach ($keys as $key) {
                if (!is_array($nodeValue) || !array_key_exists($key, $nodeValue)) {
                    continue 2; // Jump to next filter.
                }
                $nodeValue = $nodeValue[$key];
            }

            // We've found a value now let's insert it.
            $resultNode = &$result;
            foreach ($keys as $key) {
                if (!array_key_exists($key, $resultNode)) {
                    $resultNode[$key] = [];
                }
                /** @psalm-suppress MixedAssignment */
                $resultNode = &$resultNode[$key];
                /** @var array $resultNode */
            }
            /** @var array */
            $resultNode = $nodeValue;
        }

        /**
         * @psalm-suppress UnnecessaryVarAnnotation
         *
         * @var array $result
         */

        foreach ($excludeFilters as $filter) {
            $excludeNode = &$result;
            $keys = explode('.', $filter);
            $numNestedKeys = count($keys) - 1;
            foreach ($keys as $i => $key) {
                if (!is_array($excludeNode) || !array_key_exists($key, $excludeNode)) {
                    continue 2; // Jump to next filter.
                }

                if ($i < $numNestedKeys) {
                    /** @psalm-suppress MixedAssignment */
                    $excludeNode = &$excludeNode[$key];
                } else {
                    unset($excludeNode[$key]);
                    break;
                }
            }
        }

        /** @var array $result */

        return $result;
    }

    /**
     * Returns the public member variables of an object.
     *
     * This method is provided such that we can get the public member variables of an object, because a direct call of
     * {@see get_object_vars()} (within the object itself) will return only private and protected variables.
     *
     * @param object $object The object to be handled.
     *
     * @return array The public member variables of the object.
     *
     * @link https://www.php.net/manual/en/function.get-object-vars.php
     *
     * @psalm-return array<string, mixed>
     */
    public static function getObjectVars(object $object): array
    {
        return get_object_vars($object);
    }

    /**
     * Rename key in array.
     *
     * @param array $array Source array.
     * @param int|string $from Key to rename.
     * @param int|string $to New key name.
     *
     * @return array The result array.
     */
    public static function renameKey(array $array, int|string $from, int|string $to): array
    {
        if (!array_key_exists($from, $array)) {
            return $array;
        }

        $keys = array_keys($array);
        $index = array_search($from, $keys);
        $keys[$index] = $to;

        return array_combine($keys, $array);
    }

    /**
     * @param array[] $arrays
     */
    private static function doMerge(array $arrays, ?int $depth, int $currentDepth = 0): array
    {
        $result = array_shift($arrays) ?: [];
        while (!empty($arrays)) {
            foreach (array_shift($arrays) as $key => $value) {
                if (is_int($key)) {
                    if (array_key_exists($key, $result)) {
                        if ($result[$key] !== $value) {
                            $result[] = $value;
                        }
                    } else {
                        $result[$key] = $value;
                    }
                } elseif (
                    isset($result[$key])
                    && ($depth === null || $currentDepth < $depth)
                    && is_array($value)
                    && is_array($result[$key])
                ) {
                    $result[$key] = self::doMerge([$result[$key], $value], $depth, $currentDepth + 1);
                } else {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }

    private static function normalizeArrayKey(mixed $key): string
    {
        return is_float($key) ? NumericHelper::normalize($key) : (string)$key;
    }

    /**
     * @psalm-param ArrayPath $path
     *
     * @psalm-return ArrayKey
     */
    private static function parseMixedPath(array|float|int|string $path, string $delimiter): array|float|int|string
    {
        if (is_array($path)) {
            $newPath = [];
            foreach ($path as $key) {
                if (is_string($key)) {
                    $parsedPath = StringHelper::parsePath($key, $delimiter);
                    $newPath = array_merge($newPath, $parsedPath);
                    continue;
                }

                if (is_array($key)) {
                    /** @var list<float|int|string> $parsedPath */
                    $parsedPath = self::parseMixedPath($key, $delimiter);
                    $newPath = array_merge($newPath, $parsedPath);
                    continue;
                }

                $newPath[] = $key;
            }
            return $newPath;
        }

        return is_string($path) ? StringHelper::parsePath($path, $delimiter) : $path;
    }
}
