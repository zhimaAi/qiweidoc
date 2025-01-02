<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\HintKeywords\Model;


use Common\DB\BaseModel;

/**
 * @author rand
 * @ClassName RuleModel
 * @date 2024/12/315:32
 * @description
 */
class RuleModel extends BaseModel
{

    public function getTableName(): string
    {
        return "hint_keywords.rule";
    }

    protected function casts(): array
    {
        return [
            "id" => 'int',
            "created_at" => 'string',
            "updated_at" => 'string',
            "corp_id" => 'string',
            "rule_name" => 'string',
            "chat_type" => 'int',
            "group_chat_type" => 'int',
            "group_chat_id" => 'array',
            "check_user_type" => 'int',
            "hint_group_ids" => 'array',
            "hint_keywords" => 'array',
            "hint_msg_type" => 'array',
            "target_msg_type" => 'array',
            "statistic_today" => 'int',
            "statistic_yesterday" => 'int',
            "statistic_total" => 'int',
            "statistic_staff_keywords" => 'int',
            "statistic_staff_msg" => 'int',
            "statistic_cst_keywords" => 'int',
            "statistic_cst_msg" => 'int',
            "create_user_id" => 'string',
            "switch_status" => 'int',
        ];
    }
}
