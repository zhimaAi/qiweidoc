<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

namespace Common\Command;

use Common\Yii;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(name: 'jobs-status', description: '查看任务队列状态', hidden: false)]
final class JobsQueueStatusCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rpc = Yii::getRpcClient();

        try {
            // 调用 RPC 方法 jobs.QueueSize 获取所有队列状态
            $result = $rpc->call('jobs.QueueSize', '');

            if (empty($result['queues'])) {
                $output->writeln('<info>暂无队列</info>');
                return ExitCode::OK;
            }

            // 使用表格展示队列状态
            $table = new Table($output);
            $table->setHeaders(['队列名称', '等待中', '处理中', '总计']);

            $totalWaiting = 0;
            $totalProcessing = 0;
            $totalJobs = 0;
            
            foreach ($result['queues'] as $queueName => $info) {
                $waiting = $info['waiting'] ?? 0;
                $processing = $info['processing'] ?? 0;
                $total = $info['total'] ?? 0;
                
                $table->addRow([$queueName, $waiting, $processing, $total]);
                
                $totalWaiting += $waiting;
                $totalProcessing += $processing;
                $totalJobs += $total;
            }

            $table->addRow(['---', '---', '---', '---']);
            $table->addRow(['总计', $totalWaiting, $totalProcessing, $totalJobs]);

            $table->render();

            return ExitCode::OK;
        } catch (\Throwable $e) {
            $output->writeln('<error>获取队列状态失败: ' . $e->getMessage() . '</error>');
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
