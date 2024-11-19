<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Models;

use App\Libraries\Core\DB\BaseModel;
use Throwable;

class StaffModel extends BaseModel
{
    public function getTableName(): string
    {
        return "staff";
    }

    protected function getPrimaryKeys(): string | array
    {
        return "id";
    }

    protected function casts(): array
    {
        return [
            'department' => 'array',
            'extarr' => 'array',
            'order' => 'array',
            'is_leader_in_dept' => 'array',
            'direct_leader' => 'array',
            'tag_ids' => 'array',
        ];
    }

    /**
     * @throws Throwable
     */
    public static function hasConversationSave(CorpModel $corp, string $userid)
    {
        $staff = self::query()
            ->where(['and',
                ['corp_id' => $corp->get('id')],
                ['userid' => $userid],
            ])
            ->getOne();
        if (!empty($staff)) {
            $staff->update(['has_conversation' => true]);
        }
    }
}
