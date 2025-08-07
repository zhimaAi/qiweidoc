<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\ChatStatisticGroup\Model;

use Common\DB\BaseModel;

/**
 * @author rand
 * @ClassName DetailModel
 * @date 2024/12/1911:53
 * @description
 */
class DetailModel extends BaseModel
{
    protected function isAutoIncrementPK(): bool
    {
        return false;
    }

    protected array | string $primaryKeys = 'updated_at';


    public function getTableName(): string
    {
        return "chat_statistic_group.detail";
    }

    protected function casts(): array
    {
        return [
            "created_at" => 'string',
            "updated_at" => 'string',
            "corp_id" => 'string',
            "date_no" => 'string',
            "stat_time" => 'string',
            "staff_user_id" => 'string',
            "room_id" => 'string',
            "staff_msg_no_work" => 'int',
            "cst_msg_no_work" => 'int',
            "round_no" => 'int',
            "recover_in_time" => 'int',
            "at_round_no" => 'int',
            "at_num" => 'int',
            "at_recover_in_time" => 'int',
            "staff_msg_no_day" => 'int',
            "cst_msg_no_day" => 'int',
            "reply_status" => 'int',
            "promoter_type" => 'int',
            "last_msg_id" => 'string',
            "is_new_room" => 'int',
            "conversation_id" => 'string',
            "staff_self_msg_num" => 'int',
        ];
    }
}
