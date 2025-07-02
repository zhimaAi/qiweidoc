<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Queue;

/**
 * The DTO to create the SQS driver.
 *
 * @psalm-type SQSAttributesMap = array{
 *      DelaySeconds?: int<0,max>,
 *      MaximumMessageSize?: int<0,max>,
 *      MessageRetentionPeriod?: int<0,max>,
 *      Policy?: mixed,
 *      ReceiveMessageWaitTimeSeconds?: int<0,max>,
 *      RedrivePolicy?: array {
 *          deadLetterTargetArn?: mixed,
 *          maxReceiveCount: int<0,max>,
 *      },
 *      VisibilityTimeout?: int<0,max>,
 *      KmsMasterKeyId?: string,
 *      KmsDataKeyReusePeriodSeconds?: int<0,max>,
 *      ContentBasedDeduplication?: mixed,
 *      DeduplicationScope?: mixed,
 *      FifoThroughputLimit?: mixed,
 * }
 */
final class SQSCreateInfo extends CreateInfo
{
    public const PREFETCH_DEFAULT_VALUE = 10;
    public const VISIBILITY_TIMEOUT_DEFAULT_VALUE = 0;
    public const WAIT_TIME_SECONDS_DEFAULT_VALUE = 0;
    public const ATTRIBUTES_DEFAULT_VALUE = [];
    public const TAGS_DEFAULT_VALUE = [];
    public const QUEUE_DEFAULT_VALUE = 'default';
    public const MESSAGE_GROUP_ID_DEFAULT_VALUE = null;
    public const SKIP_QUEUE_DECLARATION_DEFAULT_VALUE = false;

    /**
     * @param non-empty-string $name
     * @param positive-int $priority
     * @param positive-int $prefetch
     * @param int<0, max> $visibilityTimeout
     * @param int<0, max> $waitTimeSeconds
     * @param non-empty-string $queue
     * @param array|SQSAttributesMap $attributes
     * @param array<non-empty-string, non-empty-string> $tags
     * @param non-empty-string|null $messageGroupId
     */
    public function __construct(
        string $name,
        int $priority = self::PRIORITY_DEFAULT_VALUE,
        public readonly int $prefetch = self::PREFETCH_DEFAULT_VALUE,
        public readonly int $visibilityTimeout = self::VISIBILITY_TIMEOUT_DEFAULT_VALUE,
        public readonly int $waitTimeSeconds = self::WAIT_TIME_SECONDS_DEFAULT_VALUE,
        public readonly string $queue = self::QUEUE_DEFAULT_VALUE,
        public readonly array $attributes = self::ATTRIBUTES_DEFAULT_VALUE,
        public readonly array $tags = self::TAGS_DEFAULT_VALUE,
        public readonly ?string $messageGroupId = self::MESSAGE_GROUP_ID_DEFAULT_VALUE,
        public readonly bool $skipQueueDeclaration = self::SKIP_QUEUE_DECLARATION_DEFAULT_VALUE,
    ) {
        parent::__construct(Driver::SQS, $name, $priority);

        \assert($this->prefetch >= 1, 'Precondition [prefetch >= 1] failed');
        \assert($this->visibilityTimeout >= 0, 'Precondition [visibilityTimeout >= 0] failed');
        \assert($this->waitTimeSeconds >= 0, 'Precondition [waitTimeSeconds >= 0] failed');
        \assert($this->queue !== '', 'Precondition [queue !== ""] failed');
        if ($this->messageGroupId !== null) {
            \assert($this->messageGroupId !== '', 'Precondition [messageGroupId !== ""] failed');
        }
    }

    public function toArray(): array
    {
        $result = \array_merge(parent::toArray(), [
            'prefetch' => $this->prefetch,
            'visibility_timeout' => $this->visibilityTimeout,
            'wait_time' => $this->waitTimeSeconds,
            'queue' => $this->queue,
            'skip_queue_declaration' => $this->skipQueueDeclaration,
        ]);
        if ($this->attributes !== []) {
            $result['attributes'] = $this->attributes;
        }
        if ($this->tags !== []) {
            $result['tags'] = $this->tags;
        }
        if ($this->messageGroupId !== null && $this->messageGroupId !== '') {
            $result['message_group_id'] = $this->messageGroupId;
        }

        return $result;
    }
}
