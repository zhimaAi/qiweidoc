<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Task;

use Spiral\RoadRunner\Jobs\Exception\JobsException;
use Spiral\RoadRunner\Jobs\Queue\Driver;

/**
 * @psalm-suppress MissingImmutableAnnotation The implementation of this task is mutable.
 * @method void ack()
 * @method void nack(string|\Stringable|\Throwable $message, bool $redelivery = false)
 * @method void requeue(string|\Stringable|\Throwable $message)
 */
interface ReceivedTaskInterface extends
    QueuedTaskInterface,
    WritableHeadersInterface,
    MutatesDelayInterface
{
    /**
     * Marks the current task as completed.
     *
     * @throws JobsException
     * @deprecated Since v4.5.0, use {@see ack()} instead.
     */
    public function complete(): void;

    /**
     * Marks the current task as failed.
     *
     * @throws JobsException
     * @deprecated Since v4.5.0, use {@see nack()} or {@see requeue()} instead.
     */
    public function fail(string|\Stringable|\Throwable $error, bool $requeue = false): void;

    /**
     * Returns bool {@see true} if the task is completed and {@see false}
     * otherwise.
     *
     * @psalm-mutation-free
     */
    public function isCompleted(): bool;

    /**
     * Returns bool {@see true} if the task is successful completed
     * and {@see false} otherwise.
     *
     * @psalm-mutation-free
     */
    public function isSuccessful(): bool;

    /**
     * Returns bool {@see true} if the task has been failed and {@see false}
     * otherwise.
     *
     * @psalm-mutation-free
     */
    public function isFails(): bool;

    /**
     * Returns the current broker queue name.
     *
     * @psalm-mutation-free
     * @return non-empty-string
     */
    public function getQueue(): string;


    /**
     * Returns the queue driver name.
     *
     * @psalm-mutation-free
     */
    public function getDriver(): Driver;
}
