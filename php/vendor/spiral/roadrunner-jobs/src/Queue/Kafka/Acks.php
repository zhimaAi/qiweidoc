<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Queue\Kafka;

enum Acks: string
{
    case NoAck = 'NoAck';
    case LeaderAck = 'LeaderAck';
    case AllISRAck = 'AllISRAck';
}
