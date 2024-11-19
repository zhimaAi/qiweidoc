<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

use App\Libraries\Core\Consumer\ConsumerInterface;
use Psr\Container\ContainerInterface;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Environment;
use Spiral\RoadRunner\Jobs\Jobs;
use Spiral\RoadRunner\Jobs\JobsInterface;
use Yiisoft\Injector\Injector;

return [
    JobsInterface::class => static function (ContainerInterface $container) {
        $address = Environment::fromGlobals()->getRPCAddress();

        return new Jobs(RPC::create($address));
    },

    ConsumerInterface::class => static function (ContainerInterface $container) {
        return function (string $className, array $params = []) use ($container) {
            $injector = new Injector($container);

            return $injector->make($className, $params);
        };
    },
];
