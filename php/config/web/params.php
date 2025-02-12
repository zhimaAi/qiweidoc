<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

use Yiisoft\ErrorHandler\Middleware\ErrorCatcher;
use Yiisoft\Router\Middleware\Router;
use Yiisoft\Yii\Middleware\Subfolder;

return [
    'yiisoft/input-http' => [
        'requestInputParametersResolver' => [
            'throwInputValidationException' => true,
        ],
    ],

    'middlewares' => [
        ErrorCatcher::class,
        // Subfolder::class,
        Router::class,
    ],
];
