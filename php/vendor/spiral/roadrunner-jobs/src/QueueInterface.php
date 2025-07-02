<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs;

use Spiral\RoadRunner\Jobs\Exception\JobsException;
use Spiral\RoadRunner\Jobs\Task\PreparedTaskInterface;
use Spiral\RoadRunner\Jobs\Task\QueuedTaskInterface;

/**
 * An interface that provides methods for working with a specific queue.
 */
interface QueueInterface
{
    /**
     * Returns the (non-empty) name of the queue.
     *
     * @return non-empty-string
     */
    public function getName(): string;

    /**
     * Returns the default settings (options) for all tasks created
     * within this queue.
     */
    public function getDefaultOptions(): OptionsInterface;

    /**
     * Updates all default options for all tasks created in this queue.
     *
     * Please note that the settings for already created tasks will NOT
     * be changed.
     *
     * @param OptionsInterface|null $options
     * @return $this
     */
    public function withDefaultOptions(?OptionsInterface $options): self;

    /**
     * Creates a new task to run on the specified queue.
     *
     * @param non-empty-string $name
     * @param OptionsInterface|null $options
     */
    public function create(
        string $name,
        string|\Stringable $payload,
        ?OptionsInterface $options = null,
    ): PreparedTaskInterface;

    /**
     * Sends a task to the queue.
     *
     * @throws JobsException
     */
    public function dispatch(PreparedTaskInterface $task): QueuedTaskInterface;

    /**
     * Sends multiple tasks to the queue
     *
     * @return iterable<QueuedTaskInterface>
     * @throws JobsException
     */
    public function dispatchMany(PreparedTaskInterface ...$tasks): iterable;

    /**
     * @throws JobsException
     */
    public function pause(): void;

    /**
     * @throws JobsException
     */
    public function resume(): void;

    /**
     * @throws JobsException
     */
    public function isPaused(): bool;
}
