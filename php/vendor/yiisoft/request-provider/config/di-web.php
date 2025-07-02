<?php

declare(strict_types=1);

use Yiisoft\RequestProvider\RequestProvider;
use Yiisoft\RequestProvider\RequestProviderInterface;

/** @var array $params */

return [
    RequestProviderInterface::class => [
        'class' => RequestProvider::class,
        'reset' => function () {
            /** @var RequestProvider $this */
            $this->request = null;
        },
    ],
];
