<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use Yiisoft\Definitions\Exception\InvalidConfigException;

/**
 * Allows creating an array of dynamic references from key-reference pairs.
 *
 * @see DynamicReference
 */
final class DynamicReferencesArray
{
    /**
     * Create dynamic references array from name-reference pairs.
     *
     * For example if we want to define a set of named dynamic references, usually
     * it is done as:
     *
     * ```php
     * // di-web.php
     *
     * ContentNegotiator::class => [
     *     '__construct()' => [
     *         'contentFormatters' => [
     *             'text/html' => DynamicReference::to(HtmlDataResponseFormatter::class),
     *             'application/xml' => DynamicReference::to(XmlDataResponseFormatter::class),
     *             'application/json' => DynamicReference::to(JsonDataResponseFormatter::class),
     *         ],
     *     ],
     * ],
     * ```
     *
     * That is not very convenient, so we can define formatters in a separate config and without explicitly using
     * `DynamicReference::to()` for each formatter:
     *
     * ```php
     * // params.php
     * return [
     *      'yiisoft/data-response' => [
     *          'contentFormatters' => [
     *              'text/html' => HtmlDataResponseFormatter::class,
     *              'application/xml' => XmlDataResponseFormatter::class,
     *              'application/json' => JsonDataResponseFormatter::class,
     *          ],
     *      ],
     * ];
     * ```
     *
     * Then we can use it like the following:
     *
     * ```php
     * // di-web.php
     *
     * ContentNegotiator::class => [
     *     '__construct()' => [
     *         'contentFormatters' =>
     * DynamicReferencesArray::from($params['yiisoft/data-response']['contentFormatters']),
     *     ],
     * ],
     * ```
     *
     * @param array $definitions Name-reference pairs.
     *
     * @throws InvalidConfigException
     *
     * @return DynamicReference[]
     */
    public static function from(array $definitions): array
    {
        $references = [];

        foreach ($definitions as $key => $definition) {
            $references[$key] = DynamicReference::to($definition);
        }

        return $references;
    }
}
