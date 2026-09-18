<?php

namespace Modules\Main\Cron;

use Carbon\Carbon;
use Modules\Main\Enum\EnumMediaDownloadStatus;
use Modules\Main\Model\ChatMessageModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Service\ChatSessionPullService;

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

        ChatSessionPullService::normalizeUnknownMediaDownloadStatuses();
        ChatSessionPullService::recoverStaleMediaDownloads();

        $messages = ChatMessageModel::query()
            ->where(['corp_id' => $corp->get('id')])
            ->andWhere(['media_download_status' => EnumMediaDownloadStatus::Pending->value])
            ->andWhere(['<', 'download_retry_count', ChatSessionPullService::MAX_MEDIA_DOWNLOAD_ATTEMPTS])
            ->andWhere(['<', 'msg_time', Carbon::now()->subHour()->toDateTimeString('millisecond')])
            ->andWhere(['>', 'msg_time', Carbon::now()->subDays(5)->toDateTimeString('millisecond')])
            ->orderBy(['msg_time' => SORT_ASC])
            ->limit(100)
            ->getAll();
        foreach ($messages as $message) {
            /** @var ChatMessageModel $message */
            ChatSessionPullService::dispatchMediaDownload($corp, $message);
        }
    }
}
