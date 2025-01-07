<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

use Yiisoft\Cache\File\FileCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;
use Yiisoft\Definitions\Reference;

/** @var array $params */

return [
    ConnectionInterface::class => [
        'class' => Connection::class,
        '__construct()' => [
            'driver' => new Driver(
                $params['yiisoft/db-pgsql']['dsn'],
                $params['yiisoft/db-pgsql']['username'],
                $params['yiisoft/db-pgsql']['password'],
                [
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_PERSISTENT => false,
                ],
            ),
        ],
        'setLogger()' => [Reference::to('db.logger')],
        // 'reset' => function () {
        //     $this->close();
        // },
    ],

    SchemaCache::class => [
        'class' => SchemaCache::class,
        '__construct()' => [
            new FileCache(__DIR__ . '/../../../runtime/cache'),
        ],
    ],

    Migrator::class => [
        'class' => Migrator::class,
        '__construct()' => [
            'historyTable' => $params['yiisoft/db-migration']['historyTable'],
        ],
    ],
];
