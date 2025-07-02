<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Profiler\Profiler;
use Yiisoft\Profiler\ProfilerInterface;
use Yiisoft\Profiler\Target\FileTarget;
use Yiisoft\Profiler\Target\LogTarget;

/**
 * @var array $params
 */
return [
    ProfilerInterface::class => static function (ContainerInterface $container, LoggerInterface $logger) use ($params) {
        $parameters = $params['yiisoft/profiler'];
        $targets = [];
        foreach ($parameters['targets'] as $target => $targetParams) {
            $targets[] = $container->get($target);
        }
        return new Profiler($logger, $targets);
    },
    LogTarget::class => [
        'definition' => static function (LoggerInterface $logger) use ($params) {
            $parameters = $params['yiisoft/profiler']['targets'][LogTarget::class];
            $target = (new LogTarget($logger, $parameters['level']))
                ->include($parameters['include'])
                ->exclude($parameters['exclude']);
            $target->enable((bool)$parameters['enabled']);
            return $target;
        },
        'reset' => function () use ($params) {
            $this->enable((bool)$params['yiisoft/profiler']['targets'][LogTarget::class]['enabled']);
        },
    ],
    FileTarget::class => [
        'definition' => static function (Aliases $aliases) use ($params) {
            $parameters = $params['yiisoft/profiler']['targets'][FileTarget::class];
            $target = (new FileTarget($aliases->get($parameters['filename']), $parameters['requestBeginTime'], $parameters['directoryMode']))
                ->include($parameters['include'])
                ->exclude($parameters['exclude']);

            $target->enable((bool)$parameters['enabled']);
            return $target;
        },
        'reset' => function () use ($params) {
            $this->enable((bool)$params['yiisoft/profiler']['targets'][FileTarget::class]['enabled']);
        },
    ],
];
