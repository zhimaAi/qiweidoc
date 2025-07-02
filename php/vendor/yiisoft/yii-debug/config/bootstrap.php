<?php

declare(strict_types=1);

use Yiisoft\VarDumper\VarDumper;
use Yiisoft\Yii\Debug\Collector\VarDumperCollector;
use Yiisoft\Yii\Debug\Collector\VarDumperHandlerInterfaceProxy;

/**
 * @var $params array
 */

return [
    static function ($container) use ($params) {
        if (!($params['yiisoft/yii-debug']['enabled'] ?? false)) {
            return;
        }
        if (!$container->has(VarDumperCollector::class)) {
            return;
        }

        VarDumper::setDefaultHandler(
            new VarDumperHandlerInterfaceProxy(
                VarDumper::getDefaultHandler(),
                $container->get(VarDumperCollector::class),
            ),
        );
    },
];
