<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Queue\Kafka;

final class ConsumerGroupOptions implements \JsonSerializable
{
    public const BLOCK_REBALANCE_ON_POLL_DEFAULT_VALUE = false;

    /**
     * @param non-empty-string|null $groupId sets the group to consume. Required if using group consumer.
     * @param bool $blockRebalanceOnPoll switches the client to block rebalances whenever you poll.
     */
    public function __construct(
        public readonly ?string $groupId = null,
        public bool $blockRebalanceOnPoll = self::BLOCK_REBALANCE_ON_POLL_DEFAULT_VALUE,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'group_id' => $this->groupId,
            'block_rebalance_on_poll' => $this->blockRebalanceOnPoll,
        ];
    }
}
