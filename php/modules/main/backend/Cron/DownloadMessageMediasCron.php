<?php

namespace Modules\Main\Cron;

use Carbon\Carbon;
use Modules\Main\Enum\EnumMessageType;
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
            ->andWhere(['or',
                ['and',
                    ['in', 'msg_type', ChatSessionService::ValidMediaType],
                    ['msg_content' => ''],
                ],
                ['in', 'msg_type', [
                    EnumMessageType::ChatRecord->value,
                    EnumMessageType::Mixed->value,
                    EnumMessageType::Note->value,
                ]],
            ])
            ->andWhere(['<', 'download_retry_count', ChatSessionPullService::MAX_MEDIA_DOWNLOAD_ATTEMPTS])
            ->andWhere(['<', 'msg_time', Carbon::now()->subHour()->toDateTimeString('millisecond')])
            ->andWhere(['>', 'msg_time', Carbon::now()->subDays(5)->toDateTimeString('millisecond')])
            ->orderBy(['msg_time' => SORT_ASC])
            ->limit(100)
            ->getAll();
        foreach ($messages as $message) {
            /** @var ChatMessageModel $message */
            if (in_array($message->get('msg_type'), [
                EnumMessageType::ChatRecord->value,
                EnumMessageType::Mixed->value,
                EnumMessageType::Note->value,
            ], true) && !ChatSessionPullService::hasPendingStructuredMessageMedia($message)) {
                continue;
            }
            ChatSessionPullService::dispatchMediaDownload($corp, $message);
        }
    }
}
