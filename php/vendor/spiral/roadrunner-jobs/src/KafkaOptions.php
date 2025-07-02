<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs;

final class KafkaOptions extends Options implements KafkaOptionsInterface
{
    /**
     * @param non-empty-string $topic
     * @param int<0, max> $delay
     * @param int<0, max> $priority
     * @param int<0, max> $offset
     * @param int<0, max> $partition
     */
    public function __construct(
        public string $topic,
        int $delay = self::DEFAULT_DELAY,
        int $priority = self::DEFAULT_PRIORITY,
        bool $autoAck = self::DEFAULT_AUTO_ACK,
        public string $metadata = self::DEFAULT_METADATA,
        public int $offset = self::DEFAULT_OFFSET,
        public int $partition = self::DEFAULT_PARTITION,
    ) {
        parent::__construct($delay, $priority, $autoAck);

        \assert($this->topic !== '', 'Precondition [topic !== ""] failed');
        \assert($this->partition >= 0, 'Precondition [partition >= 0] failed');
        \assert($this->offset >= 0, 'Precondition [offset >= 0] failed');
    }

    /**
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public static function from(OptionsInterface $options): self
    {
        $self = new self('default', $options->getDelay(), $options->getPriority(), $options->getAutoAck());

        if ($options instanceof KafkaOptionsInterface) {
            return $self
                ->withTopic($options->getTopic())
                ->withMetadata($options->getMetadata())
                ->withOffset($options->getOffset())
                ->withPartition($options->getPartition());
        }

        return $self;
    }

    /**
     * @psalm-immutable
     * @param int<0, max> $partition
     * @return $this
     */
    public function withPartition(int $partition): self
    {
        \assert($partition >= 0, 'Precondition [partition >= 0] failed');

        $self = clone $this;
        $self->partition = $partition;

        return $self;
    }

    /**
     * @psalm-immutable
     * @param int<0, max> $offset
     * @return $this
     */
    public function withOffset(int $offset): self
    {
        \assert($offset >= 0, 'Precondition [partition >= 0] failed');

        $self = clone $this;
        $self->offset = $offset;

        return $self;
    }

    /**
     * @psalm-immutable
     * @return $this
     */
    public function withMetadata(string $metadata): self
    {
        $self = clone $this;
        $self->metadata = $metadata;

        return $self;
    }

    /**
     * @psalm-immutable
     * @param non-empty-string $topic
     * @return $this
     */
    public function withTopic(string $topic): self
    {
        \assert($topic !== '', 'Precondition [topic !== ""] failed');

        $self = clone $this;
        $self->topic = $topic;

        return $self;
    }

    /**
     * @return non-empty-string
     */
    public function getTopic(): string
    {
        \assert($this->topic !== '', 'Precondition [topic !== ""] failed');

        return $this->topic;
    }

    public function getMetadata(): string
    {
        return $this->metadata;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getPartition(): int
    {
        return $this->partition;
    }

    public function merge(OptionsInterface $options): OptionsInterface
    {
        /** @var KafkaOptions $self */
        $self = parent::merge($options);

        if ($options instanceof KafkaOptionsInterface) {
            $self->topic = $options->getTopic();

            if (($metadata = $options->getMetadata()) !== self::DEFAULT_METADATA) {
                $self->metadata = $metadata;
            }

            if (($offset = $options->getOffset()) !== self::DEFAULT_OFFSET) {
                $self->offset = $offset;
            }

            if (($partition = $options->getPartition()) !== self::DEFAULT_PARTITION) {
                $self->partition = $partition;
            }
        }

        return $self;
    }

    public function toArray(): array
    {
        return \array_merge(parent::toArray(), [
            'topic' => $this->topic,
            'metadata' => $this->metadata,
            'offset' => $this->offset,
            'partition' => $this->partition,
        ]);
    }
}
