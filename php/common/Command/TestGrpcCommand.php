<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace Common\Command;

use GRPC\Pinger\PingerClient;
use GRPC\Pinger\PingRequest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(name: 'test-grpc', description: '测试grpc', hidden: false)]
final class TestGrpcCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $client = new PingerClient('localhost:9001', [
            'credentials' => \Grpc\ChannelCredentials::createInsecure(),
        ]);
        $request = (new PingRequest())->setUrl('https://www.baidu.com');
        list($response, $status) = $client->ping($request)->wait();
        if ($status->code != 0) {
            dump($status->details);
        } else {
            dump($response->getStatusCode());
        }

        return ExitCode::OK;
    }
}
