<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

declare(strict_types=1);

use Cycle\Database\Config\Postgres\DsnConnectionConfig;
use Cycle\Database\Config\PostgresDriverConfig;
use Cycle\Schema\Provider\FromFilesSchemaProvider;
use Cycle\Schema\Provider\SimpleCacheSchemaProvider;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Db\Pgsql\Dsn;
use Yiisoft\Definitions\Reference;
use Yiisoft\Yii\Cycle\Schema\Provider\FromConveyorSchemaProvider;

return [
    'supportEmail' => 'support@example.com',

    'yiisoft/aliases' => [
        'aliases' => [
            '@root' => dirname(__DIR__, 2),
            '@assets' => '@public/assets',
            '@assetsUrl' => '@baseUrl/assets',
            '@baseUrl' => '/',
            '@data' => '@root/data',
            '@messages' => '@resources/messages',
            '@public' => '@root/public',
            '@resources' => '@root/resources',
            '@runtime' => '@root/runtime',
            '@src' => '@root/src',
            '@tests' => '@root/tests',
            '@views' => '@root/views',
            '@vendor' => '@root/vendor',
        ],
    ],

    'yiisoft/router-fastroute' => [
        'enableCache' => false,
    ],

    'yiisoft/view' => [
        'basePath' => '@views',
        'parameters' => [
            'assetManager' => Reference::to(AssetManager::class),
        ],
    ],

    'yiisoft/yii-swagger' => [
        'annotation-paths' => [
            '@app',
        ],
    ],

    'yiisoft/db-pgsql' => [
        'dsn' => (new Dsn('pgsql', $_ENV['DB_HOST'], $_ENV['DB_DATABASE'], (string) $_ENV['DB_PORT']))->asString(),
        'username' => $_ENV['DB_USERNAME'],
        'password' => $_ENV['DB_PASSWORD'],
    ],

    'yiisoft/db-migration' => [
        'newMigrationNamespace' => 'App\\Migrations',
        'sourceNamespaces' => ['App\\Migrations'],
    ],

    'yiisoft/cache-redis' => [
        'uri' => "tcp://" . $_ENV['REDIS_HOST'] . ":" . $_ENV['REDIS_PORT'],
        'password' => $_ENV['REDIS_PASSWORD'],
    ],
];
