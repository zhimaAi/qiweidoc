<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

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

    private const LARGE_FILE_THRESHOLD = 20 * 1024 * 1024; // 20MB
    private const MAX_RETRY_COUNT = 3;

    protected string $queue = "download_session_medias";
    private const LARGE_FILE_QUEUE = "download_session_big_file";

    public function __construct(CorpModel $corp, ChatMessageModel $message)
    {
        $this->corp = $corp;
        $this->message = $message;

        if ($this->isLargeFile()) {
            $this->queue = self::LARGE_FILE_QUEUE;
        }
    }

    private function isLargeFile(): bool
    {
        return $this->message->get('msg_type') === 'file'
            && $this->message->get('raw_content')['filesize'] > self::LARGE_FILE_THRESHOLD;
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
