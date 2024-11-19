<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

/**
 * 类库统一访问入口
 */

namespace App\Libraries\Core;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Work\Application;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Spiral\Goridge\Relay;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Environment;
use Spiral\RoadRunner\Jobs\JobsInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Config\ConfigInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final class Yii
{
    private static ?ConfigInterface $config;
    private static ?ContainerInterface $container = null;

    public static function setConfig(ConfigInterface $config): void
    {
        self::$config = $config;
    }

    public static function getConfig(): ConfigInterface
    {
        if (self::$config === null) {
            throw new \RuntimeException('Config is not initialized');
        }

        return self::$config;
    }

    public static function setContainer(ContainerInterface $container): void
    {
        self::$container = $container;
    }

    public static function getContainer(): ContainerInterface
    {
        if (self::$container === null) {
            throw new \RuntimeException('Container is not initialized');
        }

        return self::$container;
    }

    public static function params()
    {
        return self::getConfig()->get('params');
    }

    public static function isDebug()
    {
        return self::params()['yiisoft/yii-debug']['enabled'];
    }

    public static function aliases(): Aliases
    {
        return self::getContainer()->get(Aliases::class);
    }

    public static function db()
    {
        return self::getContainer()->get(ConnectionInterface::class);
    }

    public static function cache(): CacheInterface
    {
        return self::getContainer()->get(CacheInterface::class);
    }

    public static function logger(string $category = ''): LoggerInterface
    {
        return self::getContainer()->get('custom.category.logger')($category);
    }

    public static function queue(string $name)
    {
        return self::getContainer()->get(JobsInterface::class)->connect($name);
    }

    public static function locker()
    {
        static $locker;
        if (empty($locker)) {
            $locker = new Locker();
        }

        return $locker;
    }

    /**
     * 获取rpc客户端实例
     */
    public static function getRpcClient(?string $address = null) : RPC
    {
        static $relay;

        if (empty($address)) {
            $address = Environment::fromGlobals()->getRPCAddress();
        }
        if (empty($relay)) {
            $relay = Relay::create($address);
        }

        return new RPC($relay);
    }

    /**
     * 获取EasyWechat对象实例
     * @throws InvalidArgumentException
     */
    public static function getEasyWechatClient(string $corpId, string $secret)
    {
        $config = [
            'corp_id' => $corpId,
            'secret' => $secret,
        ];
        $app = new Application($config);
        $app->setCache(Yii::cache()->psr());
        $app->getAccessToken();

        return $app;
    }
}
