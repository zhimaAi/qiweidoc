<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Queue;

use Spiral\RoadRunner\Jobs\Queue\AMQP\ExchangeType;

final class AMQPCreateInfo extends CreateInfo
{
    public const PREFETCH_DEFAULT_VALUE = 100;
    public const QUEUE_DEFAULT_VALUE = 'default';
    public const QUEUE_AUTO_DELETE_DEFAULT_VALUE = false;
    public const EXCHANGE_DEFAULT_VALUE = 'amqp.default';
    public const EXCHANGE_DURABLE_DEFAULT_VALUE = false;
    public const ROUTING_KEY_DEFAULT_VALUE = '';
    public const EXCLUSIVE_DEFAULT_VALUE = false;
    public const MULTIPLE_ACK_DEFAULT_VALUE = false;
    public const REQUEUE_ON_FAIL_DEFAULT_VALUE = false;
    public const DURABLE_DEFAULT_VALUE = false;
    public const CONSUME_ALL_DEFAULT_VALUE = false;
    public const QUEUE_HEADERS_DEFAULT_VALUE = [];
    public const DELETE_QUEUE_ON_STOP_DEFAULT_VALUE = false;
    public const REDIAL_TIMEOUT_DEFAULT_VALUE = 60;
    public const EXCHANGE_AUTO_DELETE_DEFAULT_VALUE = false;
    public const CONSUMER_ID_DEFAULT_VALUE = null;

    /**
     * @param non-empty-string $name
     * @param positive-int $priority
     * @param positive-int $prefetch
     * @param string $queue Queue name. Required for consumer.
     * @param non-empty-string $exchange
     * @param string $routingKey Routing key. Required for publisher.
     * @param array<string, string> $queueHeaders
     * @param positive-int $redialTimeout
     * @param non-empty-string|null $consumerId
     */
    public function __construct(
        string $name,
        int $priority = self::PRIORITY_DEFAULT_VALUE,
        public readonly int $prefetch = self::PREFETCH_DEFAULT_VALUE,
        public readonly string $queue = self::QUEUE_DEFAULT_VALUE,
        public readonly string $exchange = self::EXCHANGE_DEFAULT_VALUE,
        public readonly ExchangeType $exchangeType = ExchangeType::Direct,
        public readonly string $routingKey = self::ROUTING_KEY_DEFAULT_VALUE,
        public readonly bool $exclusive = self::EXCLUSIVE_DEFAULT_VALUE,
        public readonly bool $multipleAck = self::MULTIPLE_ACK_DEFAULT_VALUE,
        public readonly bool $requeueOnFail = self::REQUEUE_ON_FAIL_DEFAULT_VALUE,
        public readonly bool $durable = self::DURABLE_DEFAULT_VALUE,
        public readonly bool $exchangeDurable = self::EXCHANGE_DURABLE_DEFAULT_VALUE,
        public readonly bool $consumeAll = self::CONSUME_ALL_DEFAULT_VALUE,
        public readonly array $queueHeaders = self::QUEUE_HEADERS_DEFAULT_VALUE,
        public readonly bool $deleteQueueOnStop = self::DELETE_QUEUE_ON_STOP_DEFAULT_VALUE,
        public readonly int $redialTimeout = self::REDIAL_TIMEOUT_DEFAULT_VALUE,
        public readonly bool $exchangeAutoDelete = self::EXCHANGE_AUTO_DELETE_DEFAULT_VALUE,
        public readonly bool $queueAutoDelete = self::QUEUE_AUTO_DELETE_DEFAULT_VALUE,
        public readonly ?string $consumerId = self::CONSUMER_ID_DEFAULT_VALUE,
    ) {
        parent::__construct(Driver::AMQP, $name, $priority);

        \assert($this->prefetch >= 1, 'Precondition [prefetch >= 1] failed');
        \assert($this->redialTimeout >= 1, 'Precondition [redialTimeout >= 1] failed');
        \assert($this->exchange !== '', 'Precondition [exchange !== ""] failed');

        if ($this->consumerId !== null) {
            \assert($this->consumerId !== '', 'Precondition [consumerId !== ""] failed');
        }
    }

    public function toArray(): array
    {
        $result = \array_merge(parent::toArray(), [
            'prefetch' => $this->prefetch,
            'queue' => $this->queue,
            'queue_auto_delete' => $this->queueAutoDelete,
            'exchange' => $this->exchange,
            'exchange_durable' => $this->exchangeDurable,
            'exchange_type' => $this->exchangeType->value,
            'exchange_auto_delete' => $this->exchangeAutoDelete,
            'routing_key' => $this->routingKey,
            'exclusive' => $this->exclusive,
            'multiple_ack' => $this->multipleAck,
            'requeue_on_fail' => $this->requeueOnFail,
            'durable' => $this->durable,
            'consume_all' => $this->consumeAll,
            'delete_queue_on_stop' => $this->deleteQueueOnStop,
            'redial_timeout' => $this->redialTimeout,
        ]);

        if ($this->consumerId !== null && $this->consumerId !== '') {
            $result['consumer_id'] = $this->consumerId;
        }

        if ($this->queueHeaders !== []) {
            $result['queue_headers'] = $this->queueHeaders;
        }

        return $result;
    }
}
