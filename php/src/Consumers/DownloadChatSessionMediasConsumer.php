<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

/**
 * 下载会话存档资源
 */

namespace App\Consumers;

use App\Libraries\Core\Consumer\BaseConsumer;
use App\Models\ChatMessageModel;
use App\Models\CorpModel;
use App\Services\ChatSessionPullService;
use Throwable;

class DownloadChatSessionMediasConsumer extends BaseConsumer
{
    protected string $queue = "download_session_medias";

    private CorpModel $corp;

    private ChatMessageModel $message;

    public function __construct(CorpModel $corp, ChatMessageModel $message)
    {
        $this->corp = $corp;
        $this->message = $message;

        // 大于20MB的大文件放入单独的队列中
        if ($message->get('msg_type') == 'file' && $message->get('raw_content')['filesize'] > 20 * 1024 * 1024) {
            $this->queue = "download_session_big_file";
        }
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        ChatSessionPullService::handleMedia($this->corp, $this->message);
    }
}
