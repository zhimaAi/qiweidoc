<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Task;

/**
 * @psalm-immutable
 * @psalm-allow-private-mutation
 */
interface QueuedTaskInterface extends TaskInterface
{
    /**
     * Returns the unique identifier of the task in the queue.
     *
     * @psalm-mutation-free
     * @return non-empty-string
     */
    public function getId(): string;

    /**
     * Returns the (non-empty) name of the queue.
     *
     * @psalm-mutation-free
     * @return non-empty-string
     */
    public function getPipeline(): string;
}
