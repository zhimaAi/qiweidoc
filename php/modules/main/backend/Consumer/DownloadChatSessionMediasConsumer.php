<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

/**
 * 下载会话存档资源
 */

namespace Modules\Main\Consumer;

use Common\Exceptions\RetryableJobException;
use Common\Yii;
use Modules\Main\Model\ChatMessageModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Service\ChatSessionPullService;
use Throwable;

class DownloadChatSessionMediasConsumer
{
    private readonly CorpModel $corp;
    private readonly ChatMessageModel $message;

    private const MAX_RETRY_COUNT = 3;

    public function __construct(CorpModel $corp, ChatMessageModel $message)
    {
        $this->corp = $corp;
        $this->message = $message;
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $key = "download_media_retry_count_" . $this->message->get('msg_id');
        Yii::cache()->getOrSet($key, fn () => self::MAX_RETRY_COUNT, 5 * 60);

        try {
            ChatSessionPullService::handleMedia($this->corp, $this->message);
        } catch (Throwable $e) {
            $count = Yii::cache()->psr()->get($key);
            if ($count <= 0) {
                Yii::logger()->warning($e);

                return;
            }

            // 重试
            Yii::cache()->psr()->set($key, $count - 1);

            throw new RetryableJobException($e->getMessage(), 10);
        }
    }
}
