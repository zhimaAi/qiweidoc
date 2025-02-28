<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\ChatStatisticSingle\Model;

use Common\DB\BaseModel;


/**
 * @author rand
 * @ClassName SingleStatisticConfigModel
 * @date 2024/12/1910:43
 * @description
 */
class ConfigModel extends BaseModel
{

    public function getTableName(): string
    {
        return "chat_statistic_single.config";
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
            "last_stat_time" => 'string',
        ];
    }
}
