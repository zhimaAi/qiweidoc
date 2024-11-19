<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Models;

use App\ChatConversationType;
use App\ChatMessageRole;
use App\Libraries\Core\DB\BaseModel;

class ChatConversationsModel extends BaseModel
{
    public function getTableName(): string
    {
        return "chat_conversations";
    }

    protected function getPrimaryKeys(): string | array
    {
        return "id";
    }

    protected function isAutoIncrementPK(): bool
    {
        return false;
    }

    protected function casts(): array
    {
        return [
            'type' => ChatConversationType::class,
            'from_role' => ChatMessageRole::class,
            'to_role' => ChatMessageRole::class,
        ];
    }
}
