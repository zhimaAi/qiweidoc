<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Task;

use Spiral\RoadRunner\Jobs\Exception\JobsException;
use Spiral\RoadRunner\Jobs\Exception\SerializationException;
use Spiral\RoadRunner\Jobs\Queue\Driver;
use Spiral\RoadRunner\Payload;
use Spiral\RoadRunner\WorkerInterface;

/**
 * @psalm-type SuccessData = array
 * @psalm-type ErrorData = array { message: string, requeue: bool, delay_seconds: positive-int|0 }
 *
 * @psalm-import-type TypeEnum from Type
 *
 * @psalm-suppress MissingImmutableAnnotation The implementation of this task is mutable.
 */
class ReceivedTask extends QueuedTask implements ReceivedTaskInterface
{
    use WritableHeadersTrait;

    /**
     * @var TypeEnum|null
     */
    private ?int $completed = null;

    /**
     * @var int<0, max>
     */
    private int $delay = 0;

    /**
     * @param non-empty-string $id
     * @param non-empty-string $pipeline
     * @param non-empty-string $job
     * @param non-empty-string $queue
     * @param array<non-empty-string, array<string>> $headers
     */
    public function __construct(
        private readonly WorkerInterface $worker,
        string $id,
        private readonly Driver $driver,
        string $pipeline,
        string $job,
        private readonly string $queue,
        string $payload,
        array $headers = [],
    ) {
        parent::__construct($id, $pipeline, $job, $payload, $headers);
    }

    public function getDriver(): Driver
    {
        return $this->driver;
    }

    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * @deprecated Since v4.5.0, use {@see ack()} instead.
     */
    public function complete(): void
    {
        /** @psalm-suppress DeprecatedConstant */
        $this->respond(Type::SUCCESS);
    }

    /**
     * @deprecated Since v4.5.0, use {@see nack()} or {@see requeue()} instead.
     */
    public function fail(string|\Stringable|\Throwable $error, bool $requeue = false): void
    {
        $data = [
            'message' => (string)$error,
            'requeue' => $requeue,
            'delay_seconds' => $this->delay,
        ];

        if (!empty($this->headers)) {
            $data['headers'] = $this->headers;
        }

        /** @psalm-suppress DeprecatedConstant */
        $this->respond(Type::ERROR, $data);
    }

    public function ack(): void
    {
        $this->respond(Type::ACK);
    }

    /**
     * The behavior of this method depends on its implementation by the queue driver.
     */
    public function nack(string|\Stringable|\Throwable $message, bool $redelivery = false): void
    {
        $this->respond(Type::NACK, [
            'message' => (string) $message,
            'requeue' => $redelivery,
            'delay_seconds' => $this->delay,
        ]);
    }

    public function requeue(string|\Stringable|\Throwable $message): void
    {
        $data = [
            'message' => (string) $message,
            'delay_seconds' => $this->delay,
        ];

        if (!empty($this->headers)) {
            $data['headers'] = $this->headers;
        }

        $this->respond(Type::REQUEUE, $data);
    }

    public function isCompleted(): bool
    {
        return $this->completed !== null;
    }

    public function isSuccessful(): bool
    {
        /** @psalm-suppress DeprecatedConstant */
        return $this->completed === Type::SUCCESS || $this->completed === Type::ACK;
    }

    public function isFails(): bool
    {
        /** @psalm-suppress DeprecatedConstant */
        return $this->completed === Type::ERROR ||
            $this->completed === Type::NACK ||
            $this->completed === Type::REQUEUE;
    }

    /**
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function withDelay(int $seconds): self
    {
        \assert($seconds >= 0, 'Precondition [seconds >= 0] failed');

        $self = clone $this;
        $self->delay = $seconds;

        return $self;
    }

    /**
     * @param TypeEnum $type
     * @param SuccessData|ErrorData $data
     * @throws JobsException
     */
    private function respond(int $type, array $data = []): void
    {
        if ($this->completed === null) {
            try {
                $body = \json_encode(['type' => $type, 'data' => $data], JSON_THROW_ON_ERROR);

                $this->worker->respond(new Payload($body));
            } catch (\JsonException $e) {
                throw new SerializationException($e->getMessage(), $e->getCode(), $e);
            } catch (\Throwable $e) {
                throw new JobsException($e->getMessage(), (int)$e->getCode(), $e);
            }

            $this->completed = $type;
        }
    }
}
