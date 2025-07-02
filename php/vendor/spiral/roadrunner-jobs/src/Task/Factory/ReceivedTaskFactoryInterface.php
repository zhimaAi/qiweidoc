<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Task\Factory;

use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;
use Spiral\RoadRunner\Payload;

interface ReceivedTaskFactoryInterface
{
    public function create(Payload $payload): ReceivedTaskInterface;
}
