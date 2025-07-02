<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Task;

interface TaskInterface extends ProvidesHeadersInterface
{
    /**
     * Returns the (non-empty) name of the task/job.
     *
     * @psalm-mutation-free
     * @return non-empty-string
     */
    public function getName(): string;

    /**
     * Returns payload of the task/job.
     *
     * @psalm-mutation-free
     */
    public function getPayload(): string;
}
