<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Queue\Kafka;

final class ConsumerOptions implements \JsonSerializable
{
    public const CONSUMER_REGEXP_DEFAULT_VALUE = false;
    public const CONSUMER_MAX_FETCH_MESSAGE_SIZE_DEFAULT_VALUE = 50_000;
    public const CONSUMER_MIN_FETCH_MESSAGE_SIZE_DEFAULT_VALUE = 1;

    /**
     * @param non-empty-string[] $topics adds topics to use for consuming.
     * Default: empty (will produce an error), possible to use regexp if consume_regexp is set to true.
     *
     * @param bool $consumeRegexp sets the client to parse all topics passed to topics as regular expressions.
     * When consuming via regex, every metadata request loads all topics, so that all topics can be passed to
     * any regular expressions. Every topic is evaluated only once ever across all regular expressions; either it
     * permanently is known to match, or is permanently known to not match.
     *
     * @param positive-int $maxFetchMessageSize Sets the maximum amount of bytes a broker will try to send during a
     * fetch, overriding the default 50MiB.
     *
     * @param positive-int $minFetchMessageSize Sets the minimum amount of bytes a broker will try to send during a
     * fetch, overriding the default 1 byte. With the default of 1, data is sent as soon as it is available.
     *
     * @param ConsumePartition[] $consumePartitions sets partitions to consume from directly and the
     * offsets  to start consuming those partitions from. This option is basically a way to explicitly consume from
     * subsets of partitions in topics, or to consume at exact offsets.
     *
     * @param ConsumerOffset $consumerOffset Sets the offset to start consuming from, or if OffsetOutOfRange is seen
     * while fetching, to restart consuming from.
     */
    public function __construct(
        public readonly array $topics = [],
        public readonly bool $consumeRegexp = self::CONSUMER_REGEXP_DEFAULT_VALUE,
        public readonly int $maxFetchMessageSize = self::CONSUMER_MAX_FETCH_MESSAGE_SIZE_DEFAULT_VALUE,
        public readonly int $minFetchMessageSize = self::CONSUMER_MIN_FETCH_MESSAGE_SIZE_DEFAULT_VALUE,
        public readonly array $consumePartitions = [],
        public readonly ConsumerOffset $consumerOffset = new ConsumerOffset(OffsetType::AtStart, 1),
    ) {
        // A developer must specify either topics or consumePartitions or both.
        if ($this->topics === []) {
            \assert($this->consumePartitions !== [], 'Precondition [consumePartitions !== []] failed');
        } elseif ($this->consumePartitions === []) {
            \assert($this->topics !== [], 'Precondition [topics !== []] failed');
        }

        \assert($this->maxFetchMessageSize > 0, 'Precondition [maxFetchMessageSize > 0] failed');
        \assert($this->minFetchMessageSize > 0, 'Precondition [minFetchMessageSize > 0] failed');
    }

    public function jsonSerialize(): array
    {
        $data = [
            'topics' => $this->topics,
            'consume_regexp' => $this->consumeRegexp,
            'max_fetch_message_size' => $this->maxFetchMessageSize,
            'min_fetch_message_size' => $this->minFetchMessageSize,
            'consumer_offset' => $this->consumerOffset,
        ];

        foreach ($this->consumePartitions as $partition) {
            $data['consume_partitions'][$partition->topic][$partition->partition] = $partition->offset;
        }

        return $data;
    }
}
