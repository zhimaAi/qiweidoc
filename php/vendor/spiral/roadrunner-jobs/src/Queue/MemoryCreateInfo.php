<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Queue;

/**
 * The DTO to create the Memory driver.
 */
final class MemoryCreateInfo extends CreateInfo
{
    public const PREFETCH_DEFAULT_VALUE = 100_000;

    /**
     * @param non-empty-string $name
     * @param positive-int $priority
     * @param positive-int $prefetch
     */
    public function __construct(
        string $name,
        int $priority = self::PRIORITY_DEFAULT_VALUE,
        public readonly int $prefetch = self::PREFETCH_DEFAULT_VALUE,
    ) {
        parent::__construct(Driver::Memory, $name, $priority);

        \assert($this->prefetch >= 1, 'Precondition [prefetch >= 1] failed');
    }

    public function toArray(): array
    {
        return \array_merge(parent::toArray(), [
            'prefetch' => $this->prefetch,
        ]);
    }
}
