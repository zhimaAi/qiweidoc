<?php

namespace Modules\Main\Micro;

use Basis\Nats\Message\Payload;
use Basis\Nats\Service\EndpointHandler;

class TestController implements EndpointHandler
{
    public function handle(Payload $payload): array
    {
        return [
            'request' => $payload->body,
        ];
    }
}
