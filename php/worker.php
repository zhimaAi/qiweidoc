<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

use App\Libraries\Core\RoadRunnerJobsApplicationRunner;
use App\Libraries\Core\Yii;
use Spiral\RoadRunner\Environment;
use Spiral\RoadRunner\Environment\Mode;
use Yiisoft\Yii\Runner\RoadRunner\RoadRunnerGrpcApplicationRunner;
use Yiisoft\Yii\Runner\RoadRunner\RoadRunnerHttpApplicationRunner;

ini_set('display_errors', 'stderr');

require_once __DIR__ . '/autoload.php';

$env = Environment::fromGlobals();
if ($env->getMode() == Mode::MODE_HTTP) { //http环境
    $runner = (new RoadRunnerHttpApplicationRunner(
        rootPath: __DIR__,
        debug: $_ENV['YII_DEBUG'],
        checkEvents: $_ENV['YII_DEBUG'],
        environment: $_ENV['YII_ENV']
    ));
} elseif ($env->getMode() == Mode::MODE_JOBS) { //jobs环境
    $runner = (new RoadRunnerJobsApplicationRunner(
        rootPath: __DIR__,
        debug: $_ENV['YII_DEBUG'],
        checkEvents: $_ENV['YII_DEBUG'],
        environment: $_ENV['YII_ENV']
    ));
} elseif ($env->getMode() == Mode::MODE_GRPC) { //grpc环境
    $runner = (new RoadRunnerGrpcApplicationRunner(
        rootPath: __DIR__,
        debug: $_ENV['YII_DEBUG'],
        checkEvents: $_ENV['YII_DEBUG'],
        environment: $_ENV['YII_ENV']
    ));
} else {
    throw new \Exception("不支持的运行环境");
}

Yii::setConfig($runner->getConfig());
Yii::setContainer($runner->getContainer());

$runner->run();


