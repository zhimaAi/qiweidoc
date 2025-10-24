<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Common\Command;

use Common\Job\Producer;
use Modules\Main\Consumer\DownloadChatSessionBitMediasConsumer;
use Modules\Main\Model\CorpModel;
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
        $corp = CorpModel::query()->getOne();

        for ($i = 0; $i < 100; $i++) {
            Producer::dispatch(DownloadChatSessionBitMediasConsumer::class, ['corp' => $corp]);
        }

        return ExitCode::OK;
    }
}
