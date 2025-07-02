<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs;

use Spiral\RoadRunner\Jobs\Task\ProvidesHeadersInterface;
use Spiral\RoadRunner\Jobs\Task\WritableHeadersInterface;
use Spiral\RoadRunner\Jobs\Task\WritableHeadersTrait;

class Options implements OptionsInterface, WritableHeadersInterface, \JsonSerializable
{
    use WritableHeadersTrait;

    /**
     * @param positive-int|0 $delay
     * @param positive-int|0 $priority
     */
    public function __construct(
        public int $delay = self::DEFAULT_DELAY,
        public int $priority = self::DEFAULT_PRIORITY,
        public bool $autoAck = self::DEFAULT_AUTO_ACK,
    ) {
        \assert($this->delay >= 0, 'Precondition [delay >= 0] failed');
        \assert($this->priority >= 0, 'Precondition [priority >= 0] failed');
    }

    public static function from(OptionsInterface $options): self
    {
        return new self(
            $options->getDelay(),
            $options->getPriority(),
            $options->getAutoAck()
        );
    }

    /**
     * @psalm-immutable
     * @return positive-int|0
     */
    public function getDelay(): int
    {
        \assert($this->delay >= 0, 'Invariant [delay >= 0] failed');

        return $this->delay;
    }

    /**
     * @psalm-immutable
     * @return positive-int|0
     */
    public function getPriority(): int
    {
        \assert($this->priority >= 0, 'Invariant [priority >= 0] failed');

        return $this->priority;
    }

    /**
     * @psalm-immutable
     */
    public function getAutoAck(): bool
    {
        return $this->autoAck;
    }

    /**
     * @psalm-immutable
     * @param positive-int|0 $delay
     * @return $this
     */
    public function withDelay(int $delay): self
    {
        \assert($delay >= 0, 'Precondition [delay >= 0] failed');

        $self = clone $this;
        $self->delay = $delay;

        return $self;
    }

    /**
     * @psalm-immutable
     * @param positive-int|0 $priority
     * @return $this
     */
    public function withPriority(int $priority): self
    {
        \assert($priority >= 0, 'Precondition [priority >= 0] failed');

        $self = clone $this;
        $self->priority = $priority;

        return $self;
    }

    /**
     * @psalm-immutable
     * @return $this
     */
    public function withAutoAck(bool $autoAck): self
    {
        $self = clone $this;
        $self->autoAck = $autoAck;

        return $self;
    }

    public function mergeOptional(?OptionsInterface $options): OptionsInterface
    {
        if ($options === null) {
            return $this;
        }

        return $this->merge($options);
    }

    public function merge(OptionsInterface $options): OptionsInterface
    {
        $self = clone $this;

        if (($delay = $options->getDelay()) !== self::DEFAULT_DELAY) {
            $self->delay = $delay;
        }

        if (($priority = $options->getPriority()) !== self::DEFAULT_PRIORITY) {
            $self->priority = $priority;
        }

        if (($autoAck = $options->getAutoAck()) !== self::DEFAULT_AUTO_ACK) {
            $self->autoAck = $autoAck;
        }

        if ($options instanceof ProvidesHeadersInterface && ($headers = $options->getHeaders()) !== []) {
            $self->headers = $headers;
        }

        return $self;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            'priority' => $this->getPriority(),
            'delay' => $this->getDelay(),
            'auto_ack' => $this->getAutoAck(),
        ];
    }
}
