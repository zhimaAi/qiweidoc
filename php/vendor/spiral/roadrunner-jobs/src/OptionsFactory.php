<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs;

use Spiral\RoadRunner\Jobs\Queue\Driver;

final class OptionsFactory
{
    public static function create(Driver $driver): OptionsInterface
    {
        return match ($driver) {
            Driver::Kafka => new KafkaOptions('default'),
            default => new Options(),
        };
    }
}
