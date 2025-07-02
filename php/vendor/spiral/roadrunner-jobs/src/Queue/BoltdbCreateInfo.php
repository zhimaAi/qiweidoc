<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Queue;

/**
 * The DTO to create the Boltdb driver.
 */
final class BoltdbCreateInfo extends CreateInfo
{
    public const PREFETCH_DEFAULT_VALUE = 10000;
    public const FILE_DEFAULT_VALUE = 'rr.db';
    public const PERMISSIONS_DEFAULT_VALUE = 0755;

    /**
     * @param non-empty-string $name
     * @param non-empty-string $file
     * @param positive-int $priority
     * @param positive-int $prefetch
     */
    public function __construct(
        string $name,
        public readonly string $file = self::FILE_DEFAULT_VALUE,
        int $priority = self::PRIORITY_DEFAULT_VALUE,
        public readonly int $prefetch = self::PREFETCH_DEFAULT_VALUE,
        public readonly int $permissions = self::PERMISSIONS_DEFAULT_VALUE,
    ) {
        parent::__construct(Driver::BoltDB, $name, $priority);

        \assert($prefetch >= 1, 'Precondition [prefetch >= 1] failed');
        \assert($file !== '', 'Precondition [file !== ""] failed');
    }

    public function toArray(): array
    {
        return \array_merge(parent::toArray(), [
            'prefetch' => $this->prefetch,
            'file' => $this->file,
            'permissions' => $this->permissions,
        ]);
    }
}
