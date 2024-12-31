<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Model;

use Common\DB\BaseModel;
use Modules\Main\Enum\EnumChatCollectStatus;
use Modules\Main\Enum\EnumChatConversationType;
use Modules\Main\Enum\EnumChatMessageRole;

class ChatConversationsModel extends BaseModel
{
    public function getTableName(): string
    {
        return "main.chat_conversations";
    }

    protected function isAutoIncrementPK(): bool
    {
        return false;
    }

    protected function casts(): array
    {
        return [
            "id"                    => 'string',
            "created_at"            => 'string',
            "updated_at"            => 'string',
            "corp_id"               => 'string',
            "from"                  => 'string',
            "to"                    => 'string',
            "type"                  => EnumChatConversationType::class,
            "from_role"             => EnumChatMessageRole::class,
            "to_role"               => EnumChatMessageRole::class,
            "last_msg_time"         => 'string',
            "is_collect"            => EnumChatCollectStatus::class,
            "collect_reason"        => 'string',
            "collect_time"          => 'string',
            'staff_last_reply_time' => 'string',
        ];
    }
}
