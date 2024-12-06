<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

declare(strict_types=1);

use Common\Command\GetCurrentModuleInfo;
use Common\Command\GrpcGenerateCommand;
use Common\Command\HelloCommand;
use Common\Command\TestCommand;
use Common\Command\TestGrpcCommand;
use Common\Module;

return [
    'yiisoft/yii-console' => [
        'commands' => [
            'hello' => HelloCommand::class,
            'test' => TestCommand::class,
            'test-grpc' => TestGrpcCommand::class,
            'get-current-module-info' => GetCurrentModuleInfo::class,
            'generate-grpc' => GrpcGenerateCommand::class,
            ...Module::getRouterProvider()->getConsoleRouters(),
        ],
    ],
];
