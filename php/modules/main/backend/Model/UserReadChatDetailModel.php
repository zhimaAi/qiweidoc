<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Model;

use Common\DB\BaseModel;

class UserReadChatDetailModel extends BaseModel
{
    public function getTableName(): string
    {
        return "main.user_read_chat_detail";
    }

    protected function casts(): array
    {
        return [
            "id" => 'int',
            "created_at" => 'string',
            "updated_at" => 'string',
            "corp_id" => 'string',
            "users_id" => 'int',
            "conversation_id" => 'string',
        ];
    }

}
