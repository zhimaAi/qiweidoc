<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace Common\Command;

use Carbon\Carbon;
use Common\Broadcast;
use Common\HttpClient;
use Common\Job\Producer;
use Common\Yii;
use GuzzleHttp\Promise\Utils;
use Modules\Main\Consumer\SendEmailConsumer;
use Modules\Main\Model\CorpModel;
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
        ddump(Carbon::now()->toDateTimeString('m'));
        return ExitCode::OK;
    }
}
