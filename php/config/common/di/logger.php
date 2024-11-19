<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

declare(strict_types=1);

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

    LoggerInterface::class => static function (ContainerInterface $container) {
        $aliases = $container->get(Aliases::class);
        $logName = $aliases->get('@runtime/logs/' . date('Y-m-d') . '.log');
        $fileTarget = new FileTarget($logName);
        $context = [
            'env' => $_ENV['YII_ENV'],
            'mode' => Environment::fromGlobals()->getMode(),
        ];
        $contextProvider = new CompositeContextProvider(
            new SystemContextProvider(traceLevel: 3, excludedTracePaths: ['vendor/yiisoft/di']),
            new CommonContextProvider($context),
        );
        $logger = new Logger([$fileTarget], contextProvider: $contextProvider);
        $logger->setFlushInterval(1);

        return $logger;
    },

    'custom.category.logger' => static function (ContainerInterface $container) {
        return function (string $category = '') use ($container) {
            // 设置日期文件名
            $aliases = $container->get(Aliases::class);
            $logName = $aliases->get('@runtime/logs/' . date('Y-m-d') . '.log');
            $fileTarget = new FileTarget($logName);

            // 添加额外上下文
            $context = [
                'env' => $_ENV['YII_ENV'],
                'mode' => Environment::fromGlobals()->getMode(),
            ];
            if (! empty($category)) {
                $context['category'] = $category;
            }
            $contextProvider = new CompositeContextProvider(
                new SystemContextProvider(traceLevel: 3, excludedTracePaths: ['vendor/yiisoft/di']),
                new CommonContextProvider($context),
            );
            $logger = new Logger([$fileTarget], contextProvider: $contextProvider);
            $logger->setFlushInterval(1);

            return $logger;
        };
    },
];
