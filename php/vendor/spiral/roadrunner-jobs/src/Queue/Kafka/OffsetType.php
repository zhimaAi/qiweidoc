<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Queue\Kafka;

enum OffsetType: string
{
    case AtEnd = 'AtEnd';
    case At = 'At';
    case AfterMilli = 'AfterMilli';
    case AtStart = 'AtStart';
    case Relative = 'Relative';
    case WithEpoch = 'WithEpoch';
}
