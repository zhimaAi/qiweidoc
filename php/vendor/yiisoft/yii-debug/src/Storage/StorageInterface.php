<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Storage;

/**
 * Debug data storage responsibility is to store debug data from collectors added
 */
interface StorageInterface
{
    /**
     * @psalm-suppress MissingClassConstType
     */
    final public const TYPE_SUMMARY = 'summary';

    /**
     * @psalm-suppress MissingClassConstType
     */
    final public const TYPE_DATA = 'data';

    /**
     * @psalm-suppress MissingClassConstType
     */
    final public const TYPE_OBJECTS = 'objects';

    /**
     * Read all data from storage
     *
     * @param string $type Type of data being read. Available types:
     * - {@see TYPE_SUMMARY}
     * - {@see TYPE_DATA}
     * - {@see TYPE_OBJECTS}
     *
     * @return array Data from storage
     *
     * @psalm-param self::TYPE_* $type
     */
    public function read(string $type, ?string $id): array;

    /**
     * Flush data from collectors into storage
     *
     * @param array[] $data
     */
    public function write(string $id, array $data, array $objectsMap, array $summary): void;

    /**
     * Clear storage data
     */
    public function clear(): void;
}
