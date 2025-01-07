<?php

// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

namespace Common\Command;

use Common\Broadcast;
use Common\Cron;
use Common\Job\Consumer;
use Common\Micro;
use Common\Module;
use Common\Yii;
use Modules\Main\Model\CorpModel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(name: 'init-module', description: '模块启动后执行', hidden: false)]
class InitModuleCommand extends Command
{
    private ?string $moduleName = null;

    public function __construct(?string $name = null)
    {
        $this->moduleName = Module::getCurrentModuleName();

        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("执行数据库迁移");
        $this->dbMigrate();

        $output->writeln("初始化消费者");
        $consumerRouters = Module::getRouterProvider()->getConsumerRouters();
        foreach ($consumerRouters as $router) {
            /* @var Consumer $router */
            $router->register();
        }

        $output->writeln("初始化定时任务");
        $routers = Module::getRouterProvider()->getCronRouters();
        foreach ($routers as $router) {
            /* @var Cron $router */
            $router->register();
        }

        $output->writeln("初始化微服务");
        $routers = Module::getRouterProvider()->getMicroServiceRouters();
        foreach ($routers as $router) {
            /* @var Micro $micro */
            $router->register();
        }

        $output->writeln("初始化广播服务");
        $routers = Module::getRouterProvider()->getBroadcastRouters();
        foreach ($routers as $router) {
            /* @var Broadcast $router */
            $router->register();
        }

        $corp = CorpModel::query()->getOne();
        if (!empty($corp)) {
            $output->writeln("收集模块使用信息");
            $moduleConfig = Module::getLocalModuleConfig(Module::getCurrentModuleName());
            Module::collectModuleInfo($moduleConfig['name'], $moduleConfig['version'], $corp->get('id'));
        }

        $output->writeln("初始化业务");
        Module::getRouterProvider()->init();

        return ExitCode::OK;
    }

    /**
     * 执行数据库迁移
     */
    private function dbMigrate(): void
    {
        $path = Yii::aliases()->get('@modules') . "/{$this->moduleName}/yii";
        $process = new Process(['php', $path, 'migrate:up', '-n']);

        $process->mustRun();
    }
}
