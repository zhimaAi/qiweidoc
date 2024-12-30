<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace Common\Command;

use Aws\S3\S3Client;
use Modules\Main\Model\StorageModel;
use Modules\Main\Service\StorageService;
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
        return ExitCode::OK;
    }
}
