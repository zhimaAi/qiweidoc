<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Command;

use App\Consumers\SendEmailConsumer;
use App\Libraries\Core\Yii;
use Spiral\RoadRunner\Jobs\Jobs;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(name: 'test:consumer', description: '测试命令', hidden: false)]
final class TestConsumerCommand extends Command
{
    public function execute(InputInterface $input, OutputInterface $output)
    {
        for ($i = 0; $i < 10; $i++) {
            SendEmailConsumer::dispatch(['email' => '1@163.com']);
        }

        // sleep(5);
        //
        // $rpcClient = Yii::getRpcClient();
        // $jobs = new Jobs($rpcClient);
        // $jobs->pause('default');

        return ExitCode::OK;
    }
}
