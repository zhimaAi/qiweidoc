<?php

namespace Common\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(name: 'generate-grpc', description: '生成grpc代码', hidden: false)]
class GrpcGenerateCommand extends Command
{
    protected function configure()
    {
        $this->addArgument('proto', InputArgument::REQUIRED, 'proto文件');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $proto = $input->getArgument('proto');

        $process = new Process([
            "protoc",
            "--php_out=./generated",
            "--grpc_out=./generated",
            "--plugin=protoc-gen-grpc=/usr/local/bin/grpc_php_plugin",
            $proto,
        ]);
        $process->mustRun();

        $process = new Process([
            "protoc",
            "--plugin=/usr/local/bin/protoc-gen-php-grpc",
            "--php_out=./generated",
            "--php-grpc_out=./generated",
            $proto,
        ]);
        $process->mustRun();

        return ExitCode::OK;
    }
}
