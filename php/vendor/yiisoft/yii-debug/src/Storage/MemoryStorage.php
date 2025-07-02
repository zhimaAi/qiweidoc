<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Storage;

final class MemoryStorage implements StorageInterface
{
    /**
     * @psalm-var array<string, array{data: array, objects: array, summary: array}>
     */
    private array $storage = [];

    public function read(string $type, ?string $id = null): array
    {
        if ($id === null) {
            return array_map(
                static fn (array $item): array => $item[$type] ?? [],
                $this->storage,
            );
        }
        return [$id => $this->storage[$id][$type] ?? []];
    }

    public function write(string $id, array $data, array $objectsMap, array $summary): void
    {
        $this->storage[$id] = [
            'data' => $data,
            'objects' => $objectsMap,
            'summary' => $summary,
        ];
    }

    public function clear(): void
    {
        $this->storage = [];
    }
}
