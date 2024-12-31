<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Model;

use Common\DB\BaseModel;
use Modules\Main\Enum\EnumChatConversationType;
use Modules\Main\Enum\EnumChatMessageRole;

class ChatMessageModel extends BaseModel
{
    public function getTableName(): string
    {
        return "main.chat_messages";
    }

    protected function isAutoIncrementPK(): bool
    {
        return false;
    }

    protected function casts(): array
    {
        return [
            'msg_id' => 'string',
            "created_at" => 'string',
            "updated_at" => 'string',

            "corp_id" => 'string',
            "seq" => 'int',
            'public_key_ver' => 'int',
            'action' => 'string',
            'from' => 'string',
            'to_list' => 'array',
            'roomid' => 'string',
            'msg_time' => 'string',
            'msg_type' => 'string',
            'raw_content' => 'array',
            'conversation_id' => 'string',
            'from_role' => EnumChatMessageRole::class,
            'to_role' => EnumChatMessageRole::class,
            'msg_content' => 'string',
            'conversation_type' => EnumChatConversationType::class,
        ];
    }

    protected array | string $primaryKeys = 'msg_id';
}
