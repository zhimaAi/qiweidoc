<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

/**
 * 在系统重启的情况下，下载文件的消息队列可能会丢失，所以需要执行这段脚本来重新入队
 */

namespace App\Command\Once;

use App\Consumers\DownloadChatSessionMediasConsumer;
use App\Models\ChatMessageModel;
use App\Models\CorpModel;
use App\Services\ChatSessionPullService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(name: 'chat-session-media-download', description: '下载会话存档中的资源', hidden: false)]
class ChatSessionMediaDownloadCommand extends Command
{
    /**
     * @throws Throwable
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $corp = CorpModel::query()->getOne();
        if (empty($corp)) {
            return ExitCode::OK;
        }

        $messages = ChatMessageModel::query()
            ->where(['and',
                ['corp_id' => $corp->get('id')],
                ['in', 'msg_type', ChatSessionPullService::ValidMediaType],
                ['msg_content' => ''],
            ])
            ->orderBy(['seq' => SORT_DESC])
            ->getAll();
        foreach ($messages as $message) {
            DownloadChatSessionMediasConsumer::dispatch([
                'corp' => $corp,
                'message' => $message,
            ]);
        }

        return ExitCode::OK;
    }
}
