<?php

use Predis\ClientInterface;
use Psr\Container\ContainerInterface;

/** @var array $params */

return [
    ClientInterface::class => static function (ContainerInterface $container) use ($params) {
        return new Predis\Client(
            $params['predis']['uri'],
            [
                'parameters' => [
                    'password' => $params['predis']['password'],
                    'persistent' => true,
                ]
            ],
        );
    },
];
