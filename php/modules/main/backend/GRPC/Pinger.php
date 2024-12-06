<?php

namespace Modules\Main\GRPC;

use Common\HttpClient;
use GRPC\Pinger\PingerInterface;
use GRPC\Pinger\PingRequest;
use GRPC\Pinger\PingResponse;
use Spiral\RoadRunner\GRPC\ContextInterface;

final class Pinger implements PingerInterface
{
    public function ping(ContextInterface $ctx, PingRequest $in): PingResponse
    {
        $httpClient = new HttpClient();

        $response = $httpClient->get($in->getUrl());

        return new PingResponse([
            'status_code' => $response->getStatusCode(),
        ]);
    }
}
