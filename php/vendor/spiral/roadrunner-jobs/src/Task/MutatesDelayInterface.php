<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Task;

interface MutatesDelayInterface
{
    /**
     * Specify the time to wait in seconds before executing the specified task.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new task delay option.
     *
     * See {@see getDelay()} to retrieve information about the current value.
     *
     * @psalm-mutation-free
     * @param int<0, max> $seconds
     * @return static
     */
    public function withDelay(int $seconds): self;
}
