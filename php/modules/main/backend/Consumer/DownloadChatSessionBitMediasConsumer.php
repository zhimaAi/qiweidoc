<?php

namespace Modules\Main\Consumer;

use Modules\Main\Model\ChatMessageModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Service\ChatSessionPullService;
use Throwable;

readonly class DownloadChatSessionBitMediasConsumer
{
    private CorpModel $corp;
    private ChatMessageModel $message;

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
        ChatSessionPullService::handleMedia($this->corp, $this->message);
    }
}
