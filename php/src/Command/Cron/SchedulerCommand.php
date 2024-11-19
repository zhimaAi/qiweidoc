<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

declare(strict_types=1);

namespace App\Command\Cron;

use GO\Scheduler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'scheduler',
    description: '定时任务',
    hidden: false,
)]
final class SchedulerCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $schedule = new Scheduler();
        $this->registerTasks($schedule);

        while (true) {
            $waitTime = 60 - (time() % 60);
            sleep($waitTime);
            $schedule->run();
        }
    }

    /**
     * 不要在这里面执行耗时任务，因为会阻塞，应该丢给异步队列去执行
     */
    private function registerTasks(Scheduler $schedule): void
    {
        // 测试用的定时任务
        $schedule
            ->call(function () {
                echo "hello, world!\n";
            })
            ->before(function () {
                echo "测试定时任务 " . now() . PHP_EOL;
            })
            ->hourly()
            ->run();
    }
}
