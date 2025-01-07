#!/usr/bin/env php
<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

use Common\Module;
use Common\Runner\BroadcastRunner;
use Common\Runner\CronRunner;
use Common\Runner\MicroRunner;
use Common\Runner\JobsRunner;
use Common\Yii;
use Dotenv\Dotenv;
use Spiral\RoadRunner\Environment;
use Spiral\RoadRunner\Environment\Mode;
use Yiisoft\Yii\Runner\Console\ConsoleApplicationRunner;
use Yiisoft\Yii\Runner\RoadRunner\RoadRunnerHttpApplicationRunner;

ini_set('display_errors', 'stderr');

$loader = require_once __DIR__ . '/vendor/autoload.php';

date_default_timezone_set('Asia/Shanghai');
ini_set('memory_limit', '256M');

// 读取环境变量
$dotenv = Dotenv::createMutable(__DIR__ . '/../');
$dotenv->load();

// 环境配置
$_ENV['YII_ENV'] = empty($_ENV['MODE']) ? 'dev' : $_ENV['MODE'];
$_SERVER['YII_ENV'] = $_ENV['YII_ENV'];
$_ENV['YII_DEBUG'] = $_SERVER['YII_DEBUG'] = $_ENV['YII_ENV'] == 'dev';

// redis配置
$_ENV['REDIS_HOST'] = $_ENV['REDIS_HOST'] ?? 'redis';
$_ENV['REDIS_PORT'] = $_ENV['REDIS_PORT'] ?? 6379;
$_ENV['REDIS_PASSWORD'] = $_ENV['REDIS_PASSWORD'] ?? '';

// 配置数据库
$_ENV['DB_HOST'] = $_ENV['DB_HOST'] ?? 'db';
$_ENV['DB_PORT'] = $_ENV['DB_PORT'] ?? 5432;
$_ENV['DB_DATABASE'] = $_ENV['DB_DATABASE'] ?? 'postgres';
$_ENV['DB_USERNAME'] = $_ENV['DB_USERNAME'] ?? 'postgres';
$_ENV['DB_PASSWORD'] = $_ENV['DB_PASSWORD'] ?? 'postgres';

// 根据不同环境启动不同的运行模式
$env = Environment::fromGlobals();
if ($env->getMode() == Mode::MODE_HTTP) { //http
    $runner = (new RoadRunnerHttpApplicationRunner(
        rootPath: __DIR__,
        debug: $_ENV['YII_DEBUG'],
        checkEvents: $_ENV['YII_DEBUG'],
        environment: $_ENV['YII_ENV'],
    ));
} elseif ($env->getMode() == Mode::MODE_JOBS) { //jobs
    $runner = (new JobsRunner(
        rootPath: __DIR__,
        debug: $_ENV['YII_DEBUG'],
        checkEvents: $_ENV['YII_DEBUG'],
        environment: $_ENV['YII_ENV'],
    ));
} elseif ($env->getMode() == 'cron') {
    $runner = (new CronRunner(
        rootPath: __DIR__,
        debug: $_ENV['YII_DEBUG'],
        checkEvents: $_ENV['YII_DEBUG'],
        environment: $_ENV['YII_ENV'],
    ));
} elseif ($env->getMode() == 'micro') {
    $runner = (new MicroRunner(
        rootPath: __DIR__,
        debug: $_ENV['YII_DEBUG'],
        checkEvents: $_ENV['YII_DEBUG'],
        environment: $_ENV['YII_ENV'],
    ));
} elseif ($env->getMode() == 'broadcast') {
    $runner = (new BroadcastRunner(
        rootPath: __DIR__,
        debug: $_ENV['YII_DEBUG'],
        checkEvents: $_ENV['YII_DEBUG'],
        environment: $_ENV['YII_ENV'],
    ));
} else {
    $runner = new ConsoleApplicationRunner(
        rootPath: __DIR__,
        debug: $_ENV['YII_DEBUG'],
        checkEvents: $_ENV['YII_DEBUG'],
        environment: $_ENV['YII_ENV'],
    );
}

function runModule(string $moduleName)
{
    global $loader;
    global $runner;

    $_ENV['DB_USERNAME'] = $moduleName;
    $_ENV['DB_PASSWORD'] = $moduleName;

    Module::setModule('main');
    Module::loadModule($loader, 'main');

    if ($moduleName != 'main') {
        Module::setModule($moduleName);
        Module::loadModule($loader, $moduleName);
    }

    Yii::setConfig($runner->getConfig());
    Yii::setContainer($runner->getContainer());
    $runner->run();
}
