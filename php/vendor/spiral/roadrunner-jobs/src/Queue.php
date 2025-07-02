<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs;

use RoadRunner\Jobs\DTO\V1\Pipelines;
use RoadRunner\Jobs\DTO\V1\Stat;
use RoadRunner\Jobs\DTO\V1\Stats;
use Spiral\Goridge\RPC\Codec\ProtobufCodec;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\Jobs\Exception\JobsException;
use Spiral\RoadRunner\Jobs\Queue\Pipeline;
use Spiral\RoadRunner\Jobs\Task\PreparedTask;
use Spiral\RoadRunner\Jobs\Task\PreparedTaskInterface;
use Spiral\RoadRunner\Jobs\Task\ProvidesHeadersInterface;
use Spiral\RoadRunner\Jobs\Task\QueuedTaskInterface;

final class Queue implements QueueInterface
{
    private readonly Pipeline $pipeline;
    private readonly RPCInterface $rpc;
    private OptionsInterface $options;

    /**
     * @param non-empty-string $name
     */
    public function __construct(
        public readonly string $name,
        RPCInterface $rpc,
        ?OptionsInterface $options = null,
    ) {
        \assert($name !== '', 'Precondition [name !== ""] failed');

        $this->rpc = $rpc->withCodec(new ProtobufCodec());
        $this->pipeline = new Pipeline($this->getName(), $this->rpc);
        $this->options = $options ?? new Options();
    }

    public function __clone()
    {
        $this->options = clone $this->options;
    }

    public function getDefaultOptions(): OptionsInterface
    {
        return $this->options;
    }

    /**
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function withDefaultOptions(?OptionsInterface $options = null): self
    {
        $self = clone $this;
        /** @psalm-suppress PropertyTypeCoercion */
        $self->options = $options ?? new Options();

        return $self;
    }

    /**
     * Creates a new task and push it into specified queue.
     *
     * This method exists for compatibility with version RoadRunner 1.x.
     *
     * @param non-empty-string $name
     * @param OptionsInterface|null $options
     * @throws JobsException
     */
    public function push(
        string $name,
        string|\Stringable $payload,
        ?OptionsInterface $options = null,
    ): QueuedTaskInterface {
        return $this->dispatch(
            $this->create($name, $payload, $options),
        );
    }

    public function dispatch(PreparedTaskInterface $task): QueuedTaskInterface
    {
        return $this->pipeline->send($task);
    }

    public function create(
        string $name,
        string|\Stringable $payload,
        ?OptionsInterface $options = null,
    ): PreparedTaskInterface {
        if ($this->options !== null && \method_exists($this->options, 'mergeOptional')) {
            /** @var OptionsInterface $options */
            $options = $this->options->mergeOptional($options);
        }

        return new PreparedTask(
            $name,
            $payload,
            $options,
            $options instanceof ProvidesHeadersInterface ? $options->getHeaders() : []
        );
    }

    public function dispatchMany(PreparedTaskInterface ...$tasks): iterable
    {
        return $this->pipeline->sendMany($tasks);
    }

    public function pause(): void
    {
        try {
            $this->rpc->call(
                'jobs.Pause',
                new Pipelines([
                    'pipelines' => [$this->getName()],
                ]),
            );
        } catch (\Throwable $e) {
            throw new JobsException($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function resume(): void
    {
        try {
            $this->rpc->call(
                'jobs.Resume',
                new Pipelines([
                    'pipelines' => [$this->getName()],
                ]),
            );
        } catch (\Throwable $e) {
            throw new JobsException($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function isPaused(): bool
    {
        $stat = $this->getPipelineStat();

        return $stat !== null && !$stat->getReady();
    }

    /**
     * @throws JobsException
     */
    public function getPipelineStat(): ?Stat
    {
        try {
            /** @var Stats $stats */
            $stats = $this->rpc->call('jobs.Stat', '', Stats::class);
        } catch (\Throwable $e) {
            throw new JobsException($e->getMessage(), (int)$e->getCode(), $e);
        }

        /** @var Stat $stat */
        foreach ($stats->getStats() as $stat) {
            if ($stat->getPipeline() === $this->name) {
                return $stat;
            }
        }

        return null;
    }
}
