<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace Common\Command;

use Carbon\Carbon;
use Common\Broadcast;
use Common\HttpClient;
use Common\Job\Producer;
use GuzzleHttp\Promise\Utils;
use Modules\Main\Consumer\SendEmailConsumer;
use Psr\Http\Message\ResponseInterface;
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
        //Producer::dispatchCron(SendEmailConsumer::class, ['email' => '1@163.com'], '5 seconds');
        // Producer::dispatch(SendEmailConsumer::class, ['email' => '1@163.com']);
        
        // Broadcast::event("test")->send("hello, world");

        // $client = new HttpClient(['base_uri' => 'https://www.163.com']);
        // $promise1 = $client->getAsync('')->then(function (ResponseInterface $response) {
        //     echo (string)$response->getBody();
        // });
        // $promise2 = $client->getAsync('')->then(function (ResponseInterface $response) {
        //     echo (string)$response->getBody();
        // });
        // Utils::all([$promise1, $promise2])->wait();

        return ExitCode::OK;
    }
}
