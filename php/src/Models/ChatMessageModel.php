<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Models;

use App\ChatConversationType;
use App\ChatMessageRole;
use App\Libraries\Core\DB\BaseModel;

class ChatMessageModel extends BaseModel
{
    public function getTableName(): string
    {
        return "chat_messages";
    }

    protected function getPrimaryKeys(): string | array
    {
        return "msg_id";
    }

    protected function isAutoIncrementPK(): bool
    {
        return false;
    }

    protected function casts(): array
    {
        return [
            'raw_content' => 'array',
            'to_list' => 'array',
            'conversation_type' => ChatConversationType::class,
            'from_role' => ChatMessageRole::class,
            'to_role' => ChatMessageRole::class,
        ];
    }
}
