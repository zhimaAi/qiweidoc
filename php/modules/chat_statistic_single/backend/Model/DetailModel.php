<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\ChatStatisticSingle\Model;


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
        // TODO: Implement getTableName() method.
        return "chat_statistic_single.detail";
    }

    protected function casts(): array
    {
        // TODO: Implement casts() method.
        return [
            "created_at" => 'string',
            "updated_at" => 'string',
            "corp_id" => 'string',
            "date_no" => 'string',
            "stat_time" => 'string',
            "staff_user_id" => 'string',
            "external_userid" => 'string',
            "staff_msg_no_work" => 'int',
            "cst_msg_no_work" => 'int',
            "round_no" => 'int',
            "staff_msg_no_day" => 'int',
            "cst_msg_no_day" => 'int',
            "recover_in_time" => 'int',
            "first_recover_time" => 'int',
            "reply_status" => 'int',
            "promoter_type" => 'int',
            "last_msg_id" => 'string',
            "is_new_user" => 'int',
            "conversation_id" => 'string',
        ];
    }
}
