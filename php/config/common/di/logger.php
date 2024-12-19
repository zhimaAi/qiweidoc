<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

declare(strict_types=1);

use Common\Module;
use Common\RoadRunnerAppLogTarget;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Spiral\RoadRunner\Environment;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Log\ContextProvider\CommonContextProvider;
use Yiisoft\Log\ContextProvider\CompositeContextProvider;
use Yiisoft\Log\ContextProvider\SystemContextProvider;
use Yiisoft\Log\Logger;
use Yiisoft\Log\Target\File\FileTarget;

/** @var array $params */
return [
    // 默认日志
    LoggerInterface::class => static function (ContainerInterface $container) {
        $aliases = $container->get(Aliases::class);
        $logName = $aliases->get('@runtime/logs/app.log');
        $fileTarget = new FileTarget($logName);
        $context = [
            'env' => $_ENV['YII_ENV'],
            'mode' => Environment::fromGlobals()->getMode() ?: 'cli',
        ];
        $contextProvider = new CompositeContextProvider(
            new SystemContextProvider(traceLevel: 3, excludedTracePaths: ['vendor/yiisoft/di']),
            new CommonContextProvider($context),
        );
        $logger = new Logger([$fileTarget], contextProvider: $contextProvider);
        $logger->setFlushInterval(1);

        return $logger;
    },

    // 自定义日志
    'custom.logger' => static function (ContainerInterface $container) {
        return function (string $business = '') use ($container) {
            $module = Module::getCurrentModuleName();
            $context = [];
            if (!empty($business)) {
                $context = ['category' => $business];
            }

            $aliases = $container->get(Aliases::class);
            $logName = $aliases->get("@runtime/logs/{$module}/{$business}/" . date('Y-m-d') . ".log");
            $fileTarget = new FileTarget($logName);

            $logger = new Logger(
                [$fileTarget, new RoadRunnerAppLogTarget()],
                contextProvider: new CompositeContextProvider(
                    new SystemContextProvider(traceLevel: 5, excludedTracePaths: ['vendor/yiisoft/di']),
                    new CommonContextProvider($context),
                )
            );
            $logger->setFlushInterval(1);

            return $logger;
        };
    },

    // 数据库查询日志
    'db.logger' => static function (ContainerInterface $container) {
        $aliases = $container->get(Aliases::class);
        $logName = $aliases->get('@runtime/logs/db.log');
        $fileTarget = new FileTarget($logName);
        $logger = new Logger([$fileTarget], new CommonContextProvider([]));
        $logger->setFlushInterval(3);
        return $logger;
    },
];
