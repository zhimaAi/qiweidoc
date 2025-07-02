<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug;

use Psr\Container\ContainerInterface;
use Yiisoft\Di\ServiceProviderInterface;
use Yiisoft\Yii\Debug\Collector\ContainerInterfaceProxy;
use Yiisoft\Yii\Debug\Collector\ContainerProxyConfig;

final class DebugServiceProvider implements ServiceProviderInterface
{
    public function getDefinitions(): array
    {
        return [
            ContainerInterface::class =>
                static fn (ContainerInterface $container, ContainerProxyConfig $config) => new ContainerInterfaceProxy(
                    $container,
                    $config,
                ),
        ];
    }

    public function getExtensions(): array
    {
        return [];
    }
}
