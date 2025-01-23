<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Model;

use Common\DB\BaseModel;
use Modules\Main\Enum\EnumUserRoleType;
use Throwable;

class StaffModel extends BaseModel
{
    public function getTableName(): string
    {
        return "main.staff";
    }

    protected function casts(): array
    {
        return [
            'id' => 'int',
            'created_at' => 'string',
            'updated_at' => 'string',
            'name' => 'string',
            'corp_id' => 'string',
            'department' => 'array',
            'position' => 'string',
            'status' => 'int',
            'isleader' => 'int',
            'extarr' => 'array',
            'order' => 'array',
            'enable' => 'int',
            'main_department' => 'int',
            'alias' => 'string',
            'is_leader_in_dept' => 'array',
            'userid' => 'string',
            'direct_leader' => 'array',
            'tag_ids' => 'array',
            'cst_total' => 'int',
            'chat_status' => 'int',
            'has_conversation' => 'boolean',
            "role_id" => 'int',
            'can_login' => 'int',
            'enable_archive' => 'boolean',
        ];
    }

    /**
     * @throws Throwable
     */
    public static function hasConversationSave(CorpModel $corp, string $userid): void
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
