<?php

namespace Modules\Main\Command;

use Common\Job\Producer;
use Modules\Main\Consumer\SyncSessionMessageConsumer;
use Modules\Main\Model\CorpModel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(name: 'pull-chat-session-message', description: '定时同步会话消息', hidden: false)]
class PullChatSessionMessageCommand extends Command
{
    /**
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /**
         * @var CorpModel $corp
         */
        $corp = CorpModel::query()->getOne();
        if (!empty($corp)) {
            Producer::dispatch(SyncSessionMessageConsumer::class, ['corp' => $corp]);
        }

        return ExitCode::OK;
    }
}
