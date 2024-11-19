<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Models;

use App\Libraries\Core\DB\BaseModel;

class GroupModel extends BaseModel
{
    public function getTableName(): string
    {
        return "groups";
    }

    protected function getPrimaryKeys(): string | array
    {
        return "id";
    }

    protected function casts(): array
    {
        return [
            'member_list' => 'array',
            'admin_list' => 'array',
        ];
    }

    /**
     * @throws \Throwable
     */
    public static function hasConversationSave(CorpModel $corp, string $chatId)
    {
        $group = self::query()
            ->where(['and',
                ['corp_id' => $corp->get('id')],
                ['chat_id' => $chatId],
            ])
            ->getOne();
        if (!empty($group)) {
            $group->update(['has_conversation' => true]);
        }
    }
}
