<?php

declare(strict_types=1);

namespace Yiisoft\Arrays;

/**
 * `ArrayableInterface` should be implemented by classes that want to support customizable representation
 * of their instances.
 *
 * For example, if a class implements `ArrayableInterface`, by calling {@see ArrayableInterface::toArray()},
 * an instance of this class can be turned into an array (including all its embedded objects) which can
 * then be further transformed easily into other formats, such as JSON, XML.
 *
 * The methods {@see ArrayableInterface::fields()} and {@see ArrayableInterface::extraFields()} allow
 * the implementing classes to customize how and which of their data should be formatted and put into
 * the result of {@see ArrayableInterface::toArray()}.
 */
interface ArrayableInterface
{
    /**
     * Returns the list of fields that should be returned by default by {@see toArray()} when no specific
     * fields are specified.
     *
     * A field is a named element in the returned array by {@see toArray()}.
     *
     * This method should return an array of field names or field definitions.
     * If the former, the field name will be treated as an object property name whose value will be used
     * as the field value. If the latter, the array key should be the field name while the array value should be
     * the corresponding field definition which can be either an object property name or a PHP callable
     * returning the corresponding field value. The signature of the callable should be:
     *
     * ```php
     * function ($model, $field) {
     *     // return field value
     * }
     * ```
     *
     * For example, the following code declares four fields:
     *
     * - `email`: the field name is the same as the property name `email`;
     * - `firstName` and `lastName`: the field names are `firstName` and `lastName`, and their
     *   values are obtained from the `first_name` and `last_name` properties;
     * - `fullName`: the field name is `fullName`. Its value is obtained by concatenating `first_name`
     *   and `last_name`.
     *
     * ```php
     * return [
     *     'email',
     *     'firstName' => 'first_name',
     *     'lastName' => 'last_name',
     *     'fullName' => function ($model) {
     *         return $model->first_name . ' ' . $model->last_name;
     *     },
     * ];
     * ```
     *
     * @return array The list of field names or field definitions.
     *
     * @see toArray()
     */
    public function fields(): array;

    /**
     * Returns the list of additional fields that can be returned by {@see toArray()} in addition to those
     * listed in {@see fields()}.
     *
     * This method is similar to {@see fields()} except that the list of fields declared
     * by this method are not returned by default by {@see toArray()}. Only when a field in the list
     * is explicitly requested, will it be included in the result of {@see toArray()}.
     *
     * @return array The list of expandable field names or field definitions. Please refer
     * to {@see fields()} on the format of the return value.
     *
     * @see toArray()
     * @see fields()
     */
    public function extraFields(): array;

    /**
     * Converts the object into an array.
     *
     * @param array $fields the fields that the output array should contain. Fields not specified
     * in {@see fields()} will be ignored. If this parameter is empty, all fields as specified
     * in {@see fields()} will be returned.
     * @param array $expand the additional fields that the output array should contain.
     * Fields not specified in {@see extraFields()} will be ignored. If this parameter is empty, no extra fields
     * will be returned.
     * @param bool $recursive Whether to recursively return array representation of embedded objects.
     *
     * @return array The array representation of the object.
     */
    public function toArray(array $fields = [], array $expand = [], bool $recursive = true): array;
}
