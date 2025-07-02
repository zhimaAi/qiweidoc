<?php

declare(strict_types=1);

use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;
use Yiisoft\RequestProvider\RequestProviderInterface;

/** @var array $params */

return [
    BeforeMiddleware::class => [
        static function (RequestProviderInterface $provider, BeforeMiddleware $event) {
            $provider->set($event->getRequest());
        },
    ],
];
