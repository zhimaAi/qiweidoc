<?php

use Basis\Nats\Client;
use Basis\Nats\Configuration;
use Common\Yii;
use Psr\Container\ContainerInterface;

/** @var array $params */

return [
    Client::class => static function (ContainerInterface $container) use ($params) {
        return function (int $timeout = 1) use ($params) {
            $configuration = new Configuration(
                host: $params['nats-server']['endpoint'],
                timeout: $timeout,
            );
            return new Client($configuration);
        };
    },
];
