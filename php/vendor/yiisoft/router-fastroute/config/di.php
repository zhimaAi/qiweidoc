<?php

declare(strict_types=1);

use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Router\UrlGeneratorInterface;

/** @var array $params */

return [
    UrlGeneratorInterface::class => [
        'class' => UrlGenerator::class,
        'setEncodeRaw()' => [$params['yiisoft/router-fastroute']['encodeRaw']],
        'reset' => function () {
            $this->defaultArguments = [];
        },
    ],
];
