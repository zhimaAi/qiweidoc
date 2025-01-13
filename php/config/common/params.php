<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

use Common\Module;
use Yiisoft\Db\Pgsql\Dsn;

return [
    'supportEmail' => 'support@example.com',

    'yiisoft/aliases' => [
        'aliases' => [
            '@root' => dirname(__DIR__, 2),
            '@baseUrl' => '/',
            '@runtime' => '@root/runtime',
            '@src' => "@root/modules/" . Module::getCurrentModuleName(),
            '@modules' => '@root/modules',
            '@vendor' => '@root/vendor',
        ],
    ],

    'yiisoft/router-fastroute' => [
        'enableCache' => false,
    ],

    'yiisoft/db-pgsql' => [
        'dsn' => (new Dsn('pgsql', $_ENV['DB_HOST'], $_ENV['DB_DATABASE'], (string) $_ENV['DB_PORT']))->asString(),
        'username' => $_ENV['DB_USERNAME'],
        'password' => $_ENV['DB_PASSWORD'],
    ],

    'yiisoft/db-migration' => [
        'sourcePaths' => [__DIR__ . "/../../modules/" . Module::getCurrentModuleName() . "/migration"],
        'newMigrationPath' => __DIR__ . "/../../modules/" . Module::getCurrentModuleName() . "/migration",
        'historyTable' => Module::getCurrentModuleName() . ".migration",
    ],

    'yiisoft/cache-redis' => [
        'uri' => "tcp://" . $_ENV['REDIS_HOST'] . ":" . $_ENV['REDIS_PORT'],
        'password' => $_ENV['REDIS_PASSWORD'],
    ],

    'predis' => [
        'uri' => "tcp://" . $_ENV['REDIS_HOST'] . ":" . $_ENV['REDIS_PORT'],
        'password' => $_ENV['REDIS_PASSWORD'],
    ],

    'module-host' => $_ENV['MODULE_HOST'] ?? 'https://zmwk.cn',

    'internal-host' => 'http://localhost:8080',

    'local-storage' => [
        'endpoint' => $_ENV['MINIO_ENDPOINT'] ?? 'http://minio:9000',
        'region' => 'us-east-1',
        'access_key' => $_ENV['MINIO_ACCESS_KEY'] ?? 'minioadmin',
        'secret_key' => $_ENV['MINIO_SECRET_KEY'] ?? 'minioadmin',
    ],

    'nats-server' => [
        'endpoint' => $_ENV['NATS_ENDPOINT'] ?? 'tcp://nats:4222',
    ],
];
