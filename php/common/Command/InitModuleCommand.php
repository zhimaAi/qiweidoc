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

#[AsCommand(name: 'init-module', description: '模块启动后执行', hidden: false)]
class InitModuleCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('执行数据库迁移');
        $module = Module::getCurrentModuleName();
        $this->dbMigrate($module);

        $output->writeln("执行模块初始化方法");
        Module::getRouterProvider()->init();

        return ExitCode::OK;
    }

    /**
     * 执行数据库迁移
     */
    private function dbMigrate(string $moduleName): void
    {
        $process = new Process(
            ['php', 'yii', 'migrate:up', '-n'],
            Yii::aliases()->get('@root'),
            array_merge($_SERVER, $_ENV, [
                'MODULE_NAME' => $moduleName
            ])
        );

        $process->mustRun();
    }
}
