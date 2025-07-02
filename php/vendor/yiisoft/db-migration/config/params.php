<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\Command\CreateCommand;
use Yiisoft\Db\Migration\Command\DownCommand;
use Yiisoft\Db\Migration\Command\HistoryCommand;
use Yiisoft\Db\Migration\Command\NewCommand;
use Yiisoft\Db\Migration\Command\RedoCommand;
use Yiisoft\Db\Migration\Command\UpdateCommand;

return [
    'yiisoft/yii-console' => [
        'commands' => [
            'migrate:create' => CreateCommand::class,
            'migrate:down' => DownCommand::class,
            'migrate:history' => HistoryCommand::class,
            'migrate:new' => NewCommand::class,
            'migrate:redo' => RedoCommand::class,
            'migrate:up' => UpdateCommand::class,
        ],
    ],

    'yiisoft/db-migration' => [
        'newMigrationNamespace' => '',
        'newMigrationPath' => '',
        'sourceNamespaces' => [],
        'sourcePaths' => [],
    ],
];
