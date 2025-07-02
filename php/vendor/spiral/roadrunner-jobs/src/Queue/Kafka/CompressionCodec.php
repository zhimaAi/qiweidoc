<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Queue\Kafka;

enum CompressionCodec: string
{
    case Gzip = 'gzip';
    case Snappy = 'snappy';
    case Lz4 = 'lz4';
    case Zstd = 'zstd';
}
