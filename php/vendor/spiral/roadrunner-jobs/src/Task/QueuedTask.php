<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Task;

/**
 * @psalm-immutable
 * @psalm-allow-private-mutation
 */
class QueuedTask extends Task implements QueuedTaskInterface
{
    /**
     * @param non-empty-string $id
     * @param non-empty-string $pipeline
     * @param non-empty-string $name
     * @param array<non-empty-string, array<string>> $headers
     */
    public function __construct(
        protected readonly string $id,
        protected readonly string $pipeline,
        string $name,
        string $payload,
        array $headers = [],
    ) {
        parent::__construct($name, $payload, $headers);
    }

    /**
     * @return non-empty-string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return non-empty-string
     */
    public function getPipeline(): string
    {
        return $this->pipeline;
    }
}
