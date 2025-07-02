<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Task;

use Spiral\RoadRunner\Jobs\Options;
use Spiral\RoadRunner\Jobs\OptionsAwareInterface;
use Spiral\RoadRunner\Jobs\OptionsInterface;

/**
 * @psalm-suppress MissingImmutableAnnotation QueuedTask class is mutable.
 */
final class PreparedTask extends Task implements PreparedTaskInterface, OptionsAwareInterface
{
    use WritableHeadersTrait;

    private OptionsInterface $options;

    /**
     * @param non-empty-string $name
     * @param OptionsInterface|null $options
     * @param array<non-empty-string, array<string>> $headers
     */
    public function __construct(
        string $name,
        string|\Stringable $payload,
        ?OptionsInterface $options = null,
        array $headers = [],
    ) {
        $this->options = $options ?? new Options();

        parent::__construct($name, $payload, $headers);
    }

    public function __clone()
    {
        $this->options = clone $this->options;
    }

    public function getOptions(): OptionsInterface
    {
        return $this->options;
    }

    public function getDelay(): int
    {
        return $this->options->getDelay();
    }

    /**
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function withDelay(int $seconds): self
    {
        \assert($seconds >= 0, 'Precondition [seconds >= 0] failed');

        if (!\method_exists($this->options, 'withDelay')) {
            return $this;
        }

        $self = clone $this;
        /** @psalm-suppress MixedAssignment */
        $self->options = $this->options->withDelay($seconds);

        return $self;
    }

    public function getPriority(): int
    {
        return $this->options->getPriority();
    }

    /**
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function withPriority(int $priority): self
    {
        assert($priority >= 0, 'Precondition [priority >= 0] failed');

        if (!\method_exists($this->options, 'withPriority')) {
            return $this;
        }

        $self = clone $this;
        /** @psalm-suppress MixedAssignment */
        $self->options = $this->options->withPriority($priority);

        return $self;
    }

    public function getAutoAck(): bool
    {
        return $this->options->getAutoAck();
    }

    public function withAutoAck(bool $autoAck): self
    {
        if (!\method_exists($this->options, 'withAutoAck')) {
            return $this;
        }

        $self = clone $this;
        /** @psalm-suppress MixedAssignment */
        $self->options = $this->options->withAutoAck($autoAck);

        return $self;
    }

    public function withOptions(OptionsInterface $options): OptionsAwareInterface
    {
        $self = clone $this;
        $self->options = $options;

        return $self;
    }
}
