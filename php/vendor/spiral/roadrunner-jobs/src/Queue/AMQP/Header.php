<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Queue\AMQP;

interface Header
{
    public const ROUTING_KEY = 'x-routing-key';
}
