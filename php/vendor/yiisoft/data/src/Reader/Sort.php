<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader;

use InvalidArgumentException;

use function array_key_exists;
use function array_merge;
use function implode;
use function is_array;
use function is_int;
use function is_string;
use function preg_split;
use function substr;
use function trim;

/**
 * Sort represents data sorting settings:
 *
 * - A config with a map of logical field => real fields along with their order. The config also contains default
 *   order for each logical field.
 * - Currently specified logical fields order such as field1 => asc, field2 => desc. Usually it is passed directly
 *   from end user.
 *
 * Logical fields are the ones user operates with. Real fields are the ones actually present in a data set.
 * Such a mapping helps when you need to sort by a single logical field that, in fact, consists of multiple fields
 * in underlying the data set. For example, we provide a user with a username which consists of first name and last name
 * fields in actual data set.
 *
 * Based on the settings, the class can produce a criteria to be applied to {@see SortableDataInterface}
 * when obtaining the data i.e. a list of real fields along with their order directions.
 *
 * There are two modes of forming a criteria available:
 *
 * - {@see Sort::only()} ignores user-specified order for logical fields that have no configuration.
 * - {@see Sort::any()} uses user-specified logical field name and order directly for fields that have no configuration.
 *
 * @psalm-type TOrder = array<string, "asc"|"desc">
 * @psalm-type TSortFieldItem = array<string, int>
 * @psalm-type TConfigItem = array{asc: TSortFieldItem, desc: TSortFieldItem, default: "asc"|"desc"}
 * @psalm-type TConfig = array<string, TConfigItem>
 * @psalm-type TUserConfig = array<int, string>|array<string, array<string, int|string>>
 */
final class Sort
{
    /**
     * Logical fields config.
     *
     * @psalm-var TConfig
     */
    private array $config;

    /**
     * @var bool Whether to add default sorting when forming criteria.
     */
    private bool $withDefaultSorting = true;

    /**
     * @var array Logical fields to order by in form of [name => direction].
     * @psalm-var TOrder
     */
    private array $currentOrder = [];

    /**
     * @param array $config Logical fields config.
     * @psalm-param TUserConfig $config
     *
     * @param bool $ignoreExtraFields Whether to ignore logical fields not present in the config when forming criteria.
     */
    private function __construct(private bool $ignoreExtraFields, array $config)
    {
        $normalizedConfig = [];

        foreach ($config as $fieldName => $fieldConfig) {
            if (
                !(is_int($fieldName) && is_string($fieldConfig))
                && !(is_string($fieldName) && is_array($fieldConfig))
            ) {
                throw new InvalidArgumentException('Invalid config format.');
            }

            if (is_string($fieldConfig)) {
                $fieldName = $fieldConfig;
                $fieldConfig = [];
            }

            /** @psalm-var TConfig $fieldConfig */
            $normalizedConfig[$fieldName] = array_merge([
                'asc' => [$fieldName => SORT_ASC],
                'desc' => [$fieldName => SORT_DESC],
                'default' => 'asc',
            ], $fieldConfig);
        }

        /** @psalm-var TConfig $normalizedConfig */
        $this->config = $normalizedConfig;
    }

    /**
     * Create a sort instance that ignores current order for extra logical fields that have no configuration.
     *
     * @param array $config Logical fields config.
     * @psalm-param TUserConfig $config
     *
     * ```php
     * [
     *     'age', // means will be sorted as is
     *     'name' => [
     *         'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
     *         'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
     *         'default' => 'desc',
     *     ],
     * ]
     * ```
     *
     * In the above, two fields are declared: `age` and `name`. The `age` field is
     * a simple field which is equivalent to the following:
     *
     * ```php
     * 'age' => [
     *     'asc' => ['age' => SORT_ASC],
     *     'desc' => ['age' => SORT_DESC],
     *     'default' => 'asc',
     * ]
     * ```
     *
     * The name field is a virtual field name that consists of two real fields, `first_name` and `last_name`. Virtual
     * field name is used in order string or order array while real fields are used in final sorting criteria.
     *
     * Each configuration has the following options:
     *
     * - `asc` - criteria for ascending sorting.
     * - `desc` - criteria for descending sorting.
     * - `default` - default sorting. Could be either `asc` or `desc`. If not specified, `asc` is used.
     */
    public static function only(array $config): self
    {
        return new self(true, $config);
    }

    /**
     * Create a sort instance that uses logical field itself and direction provided when there is no configuration.
     *
     * @param array $config Logical fields config.
     * @psalm-param TUserConfig $config
     *
     * ```php
     * [
     *     'age', // means will be sorted as is
     *     'name' => [
     *         'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
     *         'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
     *         'default' => 'desc',
     *     ],
     * ]
     * ```
     *
     * In the above, two fields are declared: `age` and `name`. The `age` field is
     * a simple field which is equivalent to the following:
     *
     * ```php
     * 'age' => [
     *     'asc' => ['age' => SORT_ASC],
     *     'desc' => ['age' => SORT_DESC],
     *     'default' => 'asc',
     * ]
     * ```
     *
     * The name field is a virtual field name that consists of two real fields, `first_name` and `last_name`. Virtual
     * field name is used in order string or order array while real fields are used in final sorting criteria.
     *
     * Each configuration has the following options:
     *
     * - `asc` - criteria for ascending sorting.
     * - `desc` - criteria for descending sorting.
     * - `default` - default sorting. Could be either `asc` or `desc`. If not specified, `asc` is used.
     */
    public static function any(array $config = []): self
    {
        return new self(false, $config);
    }

    /**
     * Get a new instance with logical fields order set from an order string.
     *
     * The string consists of comma-separated field names.
     * If the name is prefixed with `-`, field order is descending.
     * Otherwise, the order is ascending.
     *
     * @param string $orderString Logical fields order as comma-separated string.
     *
     * @return self New instance.
     */
    public function withOrderString(string $orderString): self
    {
        $order = [];
        $parts = preg_split('/\s*,\s*/', trim($orderString), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($parts as $part) {
            if (str_starts_with($part, '-')) {
                $order[substr($part, 1)] = 'desc';
            } else {
                $order[$part] = 'asc';
            }
        }

        return $this->withOrder($order);
    }

    /**
     * Return a new instance with logical fields order set.
     *
     * @param array $order A map with logical field names to order by as keys, direction as values.
     * @psalm-param TOrder $order
     *
     * @return self New instance.
     */
    public function withOrder(array $order): self
    {
        $new = clone $this;
        $new->currentOrder = $order;
        return $new;
    }

    /**
     * Return a new instance without default sorting set.
     *
     * @return self New instance.
     */
    public function withoutDefaultSorting(): self
    {
        $new = clone $this;
        $new->withDefaultSorting = false;
        return $new;
    }

    /**
     * Get current logical fields order.
     *
     * @return array Logical fields order.
     * @psalm-return TOrder
     */
    public function getOrder(): array
    {
        return $this->currentOrder;
    }

    /**
     * Get an order string based on current logical fields order.
     *
     * The string consists of comma-separated field names.
     * If the name is prefixed with `-`, field order is descending.
     * Otherwise, the order is ascending.
     *
     * @return string An order string.
     */
    public function getOrderAsString(): string
    {
        $parts = [];

        foreach ($this->currentOrder as $field => $direction) {
            $parts[] = ($direction === 'desc' ? '-' : '') . $field;
        }

        return implode(',', $parts);
    }

    /**
     * Get a sorting criteria to be applied to {@see SortableDataInterface}
     * when obtaining the data i.e. a list of real fields along with their order directions.
     *
     * @return array Sorting criteria.
     * @psalm-return array<string, "asc"|"desc"|int>
     */
    public function getCriteria(): array
    {
        $criteria = [];
        $config = $this->config;

        foreach ($this->currentOrder as $field => $direction) {
            if (array_key_exists($field, $config)) {
                $criteria += $config[$field][$direction];
                unset($config[$field]);
            } else {
                if ($this->ignoreExtraFields) {
                    continue;
                }
                $criteria += [$field => $direction];
            }
        }

        if ($this->withDefaultSorting) {
            foreach ($config as $fieldConfig) {
                $criteria += $fieldConfig[$fieldConfig['default']];
            }
        }

        return $criteria;
    }
}
