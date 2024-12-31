<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\ChatStatisticGroup\Model;

use Common\DB\BaseModel;

/**
 * Notes: 群聊统计配置
 * User: rand
 * Date: 2024/12/27 11:28
 */
class ConfigModel extends BaseModel
{
    public function getTableName(): string
    {
        return "chat_statistic_group.config";
    }

    protected function casts(): array
    {
        return [
            "id" => 'int',
            "created_at" => 'string',
            "updated_at" => 'string',
            "corp_id" => 'string',
            "work_time" => 'array',
            "cst_keywords" => 'array',
            "staff_keywords" => 'array',
            "msg_reply_sec" => 'int',
            "at_msg_reply_sec" => 'int',
            "other_effect" => 'int',
            "group_staff_type" => 'int',
            "group_staff_ids" => 'array',
            "last_stat_time" => 'string',
        ];
    }
}
