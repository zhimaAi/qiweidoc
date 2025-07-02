<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs;

use Spiral\RoadRunner\Jobs\Exception\ReceivedTaskException;
use Spiral\RoadRunner\Jobs\Exception\SerializationException;
use Spiral\RoadRunner\Jobs\Task\Factory\ReceivedTaskFactory;
use Spiral\RoadRunner\Jobs\Task\Factory\ReceivedTaskFactoryInterface;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;
use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\WorkerInterface;

/**
 * Note that the implementation of this class depends on the environment setting
 * of the RoadRunner. In the case that the current worker does NOT meet the
 * queue processing tasks, then the Consumer class is not guaranteed to work.
 *
 * In most cases, it will be enough for you to check the availability of the
 * environment parameter:
 *
 * <code>
 *  use Spiral\RoadRunner\Environment;
 *  use Spiral\RoadRunner\Environment\Mode;
 *
 *  $env = Environment::fromGlobals();
 *  if ($env->getMode() !== Mode::MODE_JOBS) {
 *     throw new RuntimeException('Can not create Jobs Consumer');
 *  }
 *
 *  $consumer = new Consumer(...);
 * </code>
 */
final class Consumer implements ConsumerInterface
{
    private readonly WorkerInterface $worker;
    private readonly ReceivedTaskFactoryInterface $receivedTaskFactory;

    public function __construct(
        ?WorkerInterface $worker = null,
        ?ReceivedTaskFactoryInterface $receivedTaskFactory = null,
    ) {
        $this->worker = $worker ?? Worker::create();
        $this->receivedTaskFactory = $receivedTaskFactory ?? new ReceivedTaskFactory($this->worker);
    }

    /**
     * @throws ReceivedTaskException
     * @throws SerializationException
     * @psalm-suppress ArgumentTypeCoercion
     */
    public function waitTask(): ?ReceivedTaskInterface
    {
        $payload = $this->worker->waitPayload();

        if ($payload === null) {
            return null;
        }

        return $this->receivedTaskFactory->create($payload);
    }
}
