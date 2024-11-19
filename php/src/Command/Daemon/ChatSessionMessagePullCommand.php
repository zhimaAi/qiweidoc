<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Command\Daemon;

use App\Libraries\Core\Yii;
use App\Models\CorpModel;
use App\Services\ChatSessionPullService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(name: 'chat-session-message-pull', description: '定时同步会话存档消息', hidden: false)]
final class ChatSessionMessagePullCommand extends Command
{
    /**
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        while (true) {
            /** @var CorpModel $corp */
            $corp = CorpModel::query()->getOne();
            if (empty($corp)) {
                break;
            }

            try {
                ChatSessionPullService::handleMessage($corp);
            } catch (Throwable $e) {
                Yii::logger($this->getName())->log('error', $e);
                sleep(60);
            }

            sleep(5);
        }

        return ExitCode::OK;
    }
}
