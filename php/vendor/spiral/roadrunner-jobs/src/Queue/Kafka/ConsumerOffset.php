<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Queue\Kafka;

class ConsumerOffset implements \JsonSerializable
{
    /**
     * @param int<0,max>|null $value
     */
    public function __construct(
        public readonly OffsetType $type,
        public readonly ?int $value = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        $data = [
            'type' => $this->type->value,
        ];

        if ($this->value !== null) {
            $data['value'] = $this->value;
        }

        return $data;
    }
}
