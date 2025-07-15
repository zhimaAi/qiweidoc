<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

use Common\Command\DownloadMessageMediasCommand;
use Common\Command\PackModuleCommand;
use Common\Command\HelloCommand;
use Common\Command\InitModuleCommand;
use Common\Command\ResetTableOwnerCommand;
use Common\Command\TestCommand;

return [
    'yiisoft/yii-console' => [
        'commands' => [
            'hello' => HelloCommand::class,
            'test' => TestCommand::class,

            'reset-table-owner' => ResetTableOwnerCommand::class,

            'init-module' => InitModuleCommand::class,
            'pack-module' => PackModuleCommand::class,

            'download-message-media' => DownloadMessageMediasCommand::class,
        ],
    ],
];
