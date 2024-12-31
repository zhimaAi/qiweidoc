<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Common\Command;

use Basis\Nats\Message\Payload;
use Common\Yii;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(name: 'test', description: '测试命令', hidden: false)]
final class TestCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Yii::getNatsClient()->request('main.test', 'hello', function (Payload $response) {
            ddump($response->body);
        });

        return ExitCode::OK;
    }
}
