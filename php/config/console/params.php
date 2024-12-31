<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

use Common\Command\HelloCommand;
use Common\Command\InitModuleCommand;
use Common\Command\MicroServiceCommand;
use Common\Command\StartModuleCommand;
use Common\Command\TestCommand;
use Common\Module;

return [
    'yiisoft/yii-console' => [
        'commands' => [
            'hello' => HelloCommand::class,
            'test' => TestCommand::class,
            'start-module' => StartModuleCommand::class,
            'init-module' => InitModuleCommand::class,
            'micro-service' => MicroServiceCommand::class,
            ...Module::getRouterProvider()->getConsoleRouters(),
        ],
    ],
];
