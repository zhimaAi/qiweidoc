<?php

namespace Modules\Main\Cron;

use Carbon\Carbon;
use Common\Job\Producer;
use Modules\Main\Consumer\DownloadChatSessionBitMediasConsumer;
use Modules\Main\Consumer\DownloadChatSessionMediasConsumer;
use Modules\Main\Model\ChatMessageModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Service\ChatSessionPullService;
use Modules\Main\Service\ChatSessionService;

class DownloadMessageMediasCron
{
    public function __construct()
    {
    }

    public function handle()
    {
        $corp = CorpModel::query()->getOne();
        if (empty($corp)) {
            return;
        }

        $messages = ChatMessageModel::query()
            ->where(['corp_id' => $corp->get('id')])
            ->andWhere(['in', 'msg_type', ChatSessionService::ValidMediaType])
            ->andWhere(['msg_content' => ''])
            ->andWhere(['<', 'msg_time', Carbon::now()->subHour()->toDateTimeString('millisecond')])
            ->andWhere(['>', 'msg_time', Carbon::now()->subDays(5)->toDateTimeString('millisecond')])
            ->orderBy(['msg_time' => SORT_ASC])
            ->limit(100)
            ->getAll();
        foreach ($messages as $message) {
            /** @var ChatMessageModel $message */
            if (ChatSessionPullService::isLargeFile($message)) { // 大文件到单独的队列中处理
                Producer::dispatch(DownloadChatSessionBitMediasConsumer::class, ['corp' => $corp, 'message' => $message]);
            } else {
                Producer::dispatch(DownloadChatSessionMediasConsumer::class, ['corp' => $corp, 'message' => $message]);
            }
        }
    }
}
