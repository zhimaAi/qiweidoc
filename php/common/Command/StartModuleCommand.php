<?php

// Copyright © 2016- 2024 Sesame Network Technology all right reserved

declare(strict_types=1);

namespace Common\Command;

use Common\Module;
use Common\Yii;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(name: 'start-module', description: '启动模块', hidden: false)]
class StartModuleCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 获取所有模块目录
        $modules = Module::getModuleDirectories();

        // 找到可用端口
        $rpcPortList = Module::findAvailableRpcPorts(count($modules));
        $httpPortList = Module::findAvailableHttpPorts(count($modules));

        // 遍历模块数据启动模块进程
        foreach ($modules as $i => $moduleName) {

            // 获取模块配置信息
            $output->writeln("获取模块{$moduleName}的配置信息");
            $config = self::getModuleConfig($moduleName);

            // 创建数据库schema
            $output->writeln("给模块{$moduleName}创建独立的schema");
            $this->createSchema($moduleName);

            // 获取模块禁用状态
            $cacheKey = Module::getModuleRunningCacheKey($moduleName);
            if ($moduleName == 'main') {
                $paused = false;
            } else {
                $paused = Yii::cache()->getOrSet($cacheKey, function () use ($moduleName) {
                    return true;
                });
            }

            // 已被禁用的模块忽略掉，不自动启动
            if ($paused) {
                $output->writeln("模块{$moduleName}暂未开启");
                continue;
            }

            // 启动进程
            $output->writeln("启动模块{$moduleName}的独立进程");
            Module::enable($moduleName, $config, $rpcPortList[$i], $httpPortList[$i]);
        }

        return ExitCode::OK;
    }

    private function createSchema(string $moduleName)
    {
        $sql = <<<SQL
    DO $$
    BEGIN
        -- 检查并创建角色
        IF NOT EXISTS (
            SELECT 1 FROM pg_roles WHERE rolname = '{$moduleName}'
        ) THEN
            CREATE ROLE {$moduleName} WITH LOGIN PASSWORD '{$moduleName}';
        END IF;

        -- 创建 schema
        CREATE SCHEMA IF NOT EXISTS {$moduleName};

        -- 授权访问所属当前模块的所有权限
        GRANT ALL ON SCHEMA {$moduleName} TO {$moduleName};
        GRANT ALL ON ALL TABLES IN SCHEMA {$moduleName} TO {$moduleName};
        GRANT ALL ON ALL SEQUENCES IN SCHEMA {$moduleName} TO {$moduleName};
        ALTER DEFAULT PRIVILEGES IN SCHEMA {$moduleName} GRANT ALL PRIVILEGES ON TABLES TO {$moduleName};
        ALTER DEFAULT PRIVILEGES IN SCHEMA {$moduleName} GRANT ALL PRIVILEGES ON SEQUENCES TO {$moduleName};

        -- 授权访问schema public的使用权
        GRANT USAGE ON SCHEMA public TO {$moduleName};
        GRANT SELECT ON ALL TABLES IN SCHEMA public TO {$moduleName};
        ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON TABLES TO {$moduleName};

        -- 授权访问schema cron
        GRANT USAGE ON SCHEMA cron TO {$moduleName};

        -- 授权schema main的使用权
        IF '{$moduleName}' <> 'main' THEN
            GRANT USAGE ON SCHEMA main TO {$moduleName};
            GRANT SELECT ON ALL TABLES IN SCHEMA main TO {$moduleName};
            ALTER DEFAULT PRIVILEGES IN SCHEMA main GRANT SELECT ON TABLES TO {$moduleName};
            ALTER ROLE {$moduleName} SET search_path TO {$moduleName}, main, public;
        ELSE
            ALTER ROLE {$moduleName} SET search_path TO {$moduleName}, public;
        END IF;
    END $$;
SQL;
        Yii::db()->createCommand($sql)->execute();
    }

     /**
     * 获取指定模块的配置信息
     */
    public static function getModuleConfig(string $moduleName): array
    {
        Module::setModule($moduleName);
        global $loader;
        Module::loadModule($loader, $moduleName);

        $provider = Module::getRouterProvider();
        $result = $provider->getManifest();

        $consumerRouters = $provider->getConsumerRouters();
        $result['consumer_route_list'] = [];
        $consumeList = [];
        foreach ($consumerRouters as $router) {
            $consumeList[] = $router->getQueueName();

            $key = "CONSUME_" . strtoupper($router->getQueueName()) . "_COUNT";
            $result['consumer_route_list'][$key] = $router->getCount();

            $key = "CONSUME_" . strtoupper($router->getQueueName()) . "_DELETE";
            $result['consumer_route_list'][$key] = $router->getDeleteOnStop();
        }
        $result['consumer_route_list']['CONSUME_LIST'] = implode(',', $consumeList);
        $result['public_dir'] = $provider->getPublicDirectory();

        // 把模块配置信息存入缓存
        Yii::cache()->psr()->set(Module::getModuleConfigCacheKey($moduleName), $result);

        return $result;
    }
}
