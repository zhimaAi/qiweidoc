<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Task\Factory;

use JsonException;
use Spiral\RoadRunner\Jobs\Exception\ReceivedTaskException;
use Spiral\RoadRunner\Jobs\Exception\SerializationException;
use Spiral\RoadRunner\Jobs\Queue\Driver;
use Spiral\RoadRunner\Jobs\Task\KafkaReceivedTask;
use Spiral\RoadRunner\Jobs\Task\ReceivedTask;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;
use Spiral\RoadRunner\Payload;
use Spiral\RoadRunner\WorkerInterface;

/**
 * @psalm-type HeaderPayload = array{
 *    id:         non-empty-string,
 *    job:        non-empty-string,
 *    headers:    array<string, array<string>>|null,
 *    timeout:    positive-int,
 *    pipeline:   non-empty-string,
 *    driver?:    non-empty-string,
 *    queue:      non-empty-string,
 *    partition:  int<0, max>,
 *    offset:     int<0, max>,
 * }
 */
final class ReceivedTaskFactory implements ReceivedTaskFactoryInterface
{
    public function __construct(
        private readonly WorkerInterface $worker,
    ) {
    }

    /**
     * @throws SerializationException
     * @throws ReceivedTaskException
     * @psalm-suppress ArgumentTypeCoercion
     */
    public function create(Payload $payload): ReceivedTaskInterface
    {
        $header = $this->getHeader($payload);
        $headers = (array) $header['headers'];

        $id = $header['id'];
        $job = $header['job'];
        $driver = Driver::tryFrom($header['driver'] ?? 'unknown') ?? Driver::Unknown;
        $queue = $header['queue'] ?? 'unknown';
        $pipeline = $header['pipeline'] ?? 'unknown';

        return match ($driver) {
            Driver::Kafka => new KafkaReceivedTask(
                $this->worker,
                $id,
                $pipeline,
                $job,
                $queue, // Kafka topic name
                (int)$header['partition'] ?? 0,
                (int)$header['offset'] ?? 0,
                $payload->body,
                $headers
            ),
            default => new ReceivedTask(
                $this->worker,
                $id,
                $driver,
                $pipeline,
                $job,
                $queue, // Queue broker queue name
                $payload->body,
                $headers
            ),
        };
    }

    /**
     * @psalm-suppress MixedReturnTypeCoercion
     *
     * @return HeaderPayload
     * @throws SerializationException
     * @throws ReceivedTaskException
     */
    private function getHeader(Payload $payload): array
    {
        if (empty($payload->header)) {
            throw new ReceivedTaskException('Task payload does not have a valid header.');
        }

        try {
            return (array)\json_decode($payload->header, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new SerializationException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
