<?php

use Basis\Nats\Client;
use Basis\Nats\Configuration;
use Common\Yii;
use Psr\Container\ContainerInterface;

/** @var array $params */

return [
    Client::class => static function (ContainerInterface $container) use ($params) {
        $configuration = new Configuration(
            host: $params['nats-server']['endpoint'],
        );
        return new Client($configuration, Yii::logger());
    },
];
