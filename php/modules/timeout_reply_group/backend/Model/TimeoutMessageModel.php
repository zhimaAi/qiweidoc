<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\TimeoutReplyGroup\Model;

use Common\DB\BaseModel;
use Throwable;

class TimeoutMessageModel extends BaseModel
{
    public function getTableName(): string
    {
        return "timeout_reply_group.timeout_messages";
    }

    protected function casts(): array
    {
        return [
            'id'                => 'int',
            'created_at'        => 'string',
            'updated_at'        => 'string',
            'corp_id'           => 'string',
            'msg_id'            => 'string',
            'rule_id'           => 'int',
            'remind_rule_index' => 'int',
        ];
    }
}
