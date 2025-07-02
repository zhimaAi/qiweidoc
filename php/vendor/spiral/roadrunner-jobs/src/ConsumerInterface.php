<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs;

use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;

/**
 * An interface that contains all the required methods of the consumer: The
 * handler of all incoming messages from the RoadRunner.
 */
interface ConsumerInterface
{
    /**
     * A method that blocks the current execution thread and waits for
     * incoming tasks.
     *
     * <code>
     *  while($task = $consumer->waitTask()) {
     *     // Do something with received $task
     *     var_dump($task);
     *  }
     * </code>
     */
    public function waitTask(): ?ReceivedTaskInterface;
}
