<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Queue\Kafka;

final class ConsumePartition
{
    /**
     * @param non-empty-string $topic
     * @param int<0, max> $partition
     */
    public function __construct(
        public readonly string $topic,
        public readonly int $partition,
        public readonly ConsumerOffset $offset,
    ) {
        \assert($this->topic !== '', 'Precondition [topic !== \'\'] failed');
        \assert($this->partition >= 0, 'Precondition [partition >= 0] failed');
    }
}
