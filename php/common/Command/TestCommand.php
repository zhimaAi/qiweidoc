<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace Common\Command;

use Common\Yii;
use Grpc\ChannelCredentials;
use GRPC\Pinger\PingerClient;
use GRPC\Pinger\PingRequest;
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
        $rpcClient = Yii::getDefaultRpcClient();
        $result = $rpcClient->call('minio.UploadFile', ['file_path' => '/var/www/a.txt', 'origin_file_name' => 'ddd']);
        ddump($result);

        return ExitCode::OK;
    }
}
