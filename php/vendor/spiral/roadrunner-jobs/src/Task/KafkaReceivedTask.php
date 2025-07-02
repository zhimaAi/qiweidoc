<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Task;

use Spiral\RoadRunner\Jobs\Queue\Driver;
use Spiral\RoadRunner\WorkerInterface;

/**
 * @psalm-suppress MutableDependency, MissingImmutableAnnotation
 */
final class KafkaReceivedTask extends ReceivedTask
{
    /**
     * @param non-empty-string $id
     * @param non-empty-string $pipeline
     * @param non-empty-string $job
     * @param non-empty-string $topic
     * @param int<0, max> $partition
     * @param int<0, max> $offset
     * @param array<non-empty-string, array<string>> $headers
     */
    public function __construct(
        WorkerInterface $worker,
        string $id,
        string $pipeline,
        string $job,
        string $topic,
        private readonly int $partition,
        private readonly int $offset,
        string $payload = '',
        array $headers = [],
    ) {
        parent::__construct($worker, $id, Driver::Kafka, $pipeline, $job, $topic, $payload, $headers);
    }

    /**
     * @return positive-int|0
     */
    public function getPartition(): int
    {
        return $this->partition;
    }

    /**
     * @return int<0, max>
     */
    public function getOffset(): int
    {
        return $this->offset;
    }
}
