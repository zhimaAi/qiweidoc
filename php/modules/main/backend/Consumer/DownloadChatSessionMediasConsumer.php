<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

/**
 * 下载会话存档资源
 */

namespace Modules\Main\Consumer;

use Modules\Main\Enum\EnumMessageType;
use Modules\Main\Model\ChatMessageModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Service\ChatSessionPullService;
use Throwable;

class DownloadChatSessionMediasConsumer
{
    private readonly CorpModel $corp;
    private readonly ChatMessageModel $message;

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
        if (in_array($this->message->get('msg_type'), [
            EnumMessageType::ChatRecord->value,
            EnumMessageType::Mixed->value,
            EnumMessageType::Note->value,
        ], true)) {
            ChatSessionPullService::handleStructuredMessageMedias($this->corp, $this->message);
            return;
        }

        ChatSessionPullService::handleMedia($this->corp, $this->message);
    }
}
