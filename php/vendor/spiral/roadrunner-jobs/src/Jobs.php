<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs;

use RoadRunner\Jobs\DTO\V1\DeclareRequest;
use RoadRunner\Jobs\DTO\V1\Pipelines;
use Spiral\Goridge\RPC\Codec\ProtobufCodec;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\Jobs\Exception\JobsException;
use Spiral\RoadRunner\Jobs\Queue\CreateInfoInterface;

/**
 * @psalm-import-type CreateInfoArrayType from CreateInfoInterface
 */
final class Jobs implements JobsInterface
{
    private readonly RPCInterface $rpc;

    public function __construct(RPCInterface $rpc)
    {
        $this->rpc = $rpc->withCodec(new ProtobufCodec());
    }

    public function create(CreateInfoInterface $info, ?OptionsInterface $options = null): QueueInterface
    {
        try {
            $this->rpc->call(
                'jobs.Declare',
                new DeclareRequest([
                    'pipeline' => $this->toStringOfStringMap($info->toArray()),
                ]),
            );

            return $this->connect($info->getName(), $options ?? OptionsFactory::create($info->getDriver()));
        } catch (\Throwable $e) {
            throw new JobsException($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * @param non-empty-string $queue
     */
    public function connect(string $queue, ?OptionsInterface $options = null): QueueInterface
    {
        \assert($queue !== '', 'Precondition [queue !== ""] failed');

        return new Queue($queue, $this->rpc, $options);
    }

    public function pause(string|QueueInterface $queue, string|QueueInterface ...$queues): void
    {
        try {
            $this->rpc->call(
                'jobs.Pause',
                new Pipelines([
                    'pipelines' => $this->names($queue, ...$queues),
                ]),
            );
        } catch (\Throwable $e) {
            throw new JobsException($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function resume(QueueInterface|string $queue, QueueInterface|string ...$queues): void
    {
        try {
            $this->rpc->call(
                'jobs.Resume',
                new Pipelines([
                    'pipelines' => $this->names($queue, ...$queues),
                ]),
            );
        } catch (\Throwable $e) {
            throw new JobsException($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * @return int<0, max>
     * @throws JobsException
     */
    public function count(): int
    {
        return \iterator_count($this->getIterator());
    }

    /**
     * @return \Traversable<non-empty-string, QueueInterface>
     * @throws JobsException
     */
    public function getIterator(): \Traversable
    {
        try {
            /** @var Pipelines $result */
            $result = $this->rpc->call('jobs.List', '', Pipelines::class);

            /** @psalm-var non-empty-string $queue */
            foreach ($result->getPipelines() as $queue) {
                yield $queue => $this->connect($queue);
            }
        } catch (\Throwable $e) {
            throw new JobsException($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * @param CreateInfoArrayType $map
     * @return string[]
     * @throws \Throwable
     * @psalm-suppress MixedAssignment
     */
    private function toStringOfStringMap(array $map): array
    {
        $marshalled = [];

        foreach ($map as $key => $value) {
            $marshalled[$key] = match (true) {
                \is_int($value) => (string)$value,
                \is_object($value) && \method_exists($value, '__toString') => (string)$value->__toString(),
                $value instanceof \Stringable => $value->__toString(),
                \is_string($value) => $value,
                \is_bool($value) => $value ? 'true' : 'false',
                $value instanceof \JsonSerializable,
                \is_array($value) => \json_encode($value, \JSON_THROW_ON_ERROR),
                default => throw new \InvalidArgumentException(
                    \sprintf('Can not cast to string unrecognized value of type %s', \get_debug_type($value)),
                ),
            };
        }

        return $marshalled;
    }

    /**
     * @param QueueInterface|non-empty-string ...$queues
     * @return array<non-empty-string>
     */
    private function names(QueueInterface|string ...$queues): array
    {
        $names = [];

        foreach ($queues as $queue) {
            if ($queue instanceof QueueInterface) {
                $queue = $queue->getName();
            }

            $names[] = $queue;
        }

        return $names;
    }
}
