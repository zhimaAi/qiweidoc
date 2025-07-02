<?php

declare(strict_types=1);

namespace Yiisoft\Config\Modifier;

final class RemoveFromVendor
{
    /**
     * Marks specified keys to be ignored when reading configuration from vendor packages.
     * Nested keys are supported only when {@see RecursiveMerge} modifier is applied as well.
     *
     * The modifier should be specified as
     *
     * ```php
     * // Remove elements `key-for-remove` and `nested→key→for-remove` from all groups in all vendor packages
     * RemoveFromVendor::keys(
     *   ['key-for-remove'],
     *   ['nested', 'key', 'for-remove'],
     * ),
     *
     * // Remove elements `a` and `b` from all groups in package `yiisoft/auth`
     * RemoveFromVendor::keys(['a'], ['b'])
     *   ->package('yiisoft/auth'),
     *
     * // Remove elements `c` and `d` from groups `params` and `web` in package `yiisoft/view`
     * RemoveFromVendor::keys(['c'], ['d'])
     *   ->package('yiisoft/view', 'params', 'web'),
     *
     * // Remove elements `e` and `f` from all groups in package `yiisoft/auth`
     * // and from groups `params` and `web` in package `yiisoft/view`
     * RemoveFromVendor::keys(['e'], ['f'])
     *   ->package('yiisoft/auth')
     *   ->package('yiisoft/view', 'params', 'web'),
     * ```
     *
     * For example:
     *
     * - configuration in application `composer.json`:
     *
     * ```
     * "config-plugin": {
     *     "events": "events.php",
     *     "params": "params.php",
     * }
     * ```
     *
     * - application `events.php` contents:
     *
     * ```php
     * return ['a' => 1, 'b' => 2];
     * ```
     *
     * - configuration in vendor package:
     *
     * ```
     * "config-plugin": {
     *     "events": "events.php",
     * }
     * ```
     *
     * - vendor package `events.php` contents:
     *
     * ```php
     * return ['c' => 3, 'd' => 4, 'e' => 5];
     * ```
     *
     * - getting configuration:
     *
     * ```php
     * $config = new Config(new ConfigPaths($configsDir), null, [
     *     RemoveFromVendor::keys(
     *         ['d'],
     *         ['e'],
     *     )
     * ]);
     *
     * $result = $config->get('events');
     * ```
     *
     * The result will be:
     *
     * ```php
     * [
     *     'c' => 3,
     *     'a' => 1,
     *     'b' => 2,
     * ]
     * ```
     *
     * @param string[] ...$keys
     */
    public static function keys(array ...$keys): RemoveKeysFromVendor
    {
        return new RemoveKeysFromVendor(...$keys);
    }

    /**
     * Marks specified groups to be ignored when reading configuration from vendor packages.
     *
     * The modifier should be specified as
     *
     * ```php
     * RemoveFromVendor::groups([
     *   // Remove group `params` from all vendor packages
     *   '*' => 'params',
     *
     *   // Remove groups `common` and `web` from all vendor packages
     *   '*' => ['common', 'web'],
     *
     *   // Remove all groups from package `yiisoft/auth`
     *   'yiisoft/auth' => '*',
     *
     *   // Remove groups `params` from package `yiisoft/http`
     *   'yiisoft/http' => 'params',
     *
     *   // Remove groups `params` and `common` from package `yiisoft/view`
     *   'yiisoft/view' => ['params', 'common'],
     *   ]),
     * ```
     *
     * @psalm-param array<string,string|string[]> $groups
     */
    public static function groups(array $groups): RemoveGroupsFromVendor
    {
        return new RemoveGroupsFromVendor($groups);
    }
}
