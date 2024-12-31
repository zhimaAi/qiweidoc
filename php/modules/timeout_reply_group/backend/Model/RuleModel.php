<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\TimeoutReplyGroup\Model;

use Common\DB\BaseModel;
use Modules\TimeoutReplyGroup\Enum\EnumGroupType;
use Modules\TimeoutReplyGroup\Enum\EnumInspectTimeType;
use Modules\TimeoutReplyGroup\Enum\EnumTimeUnitType;

class RuleModel extends BaseModel
{
    public function getTableName(): string
    {
        return "timeout_reply_group.rules";
    }

    protected function casts(): array
    {
        return [
            'id'                            => 'int',
            'created_at'                    => 'string',
            'updated_at'                    => 'string',
            'corp_id'                       => 'string',
            'name'                          => 'string',
            'group_type'                    => EnumGroupType::class,
            'group_chat_id_list'            => 'array',
            'group_staff_userid_list'       => 'array',
            'group_keyword_list'            => 'array',
            'inspect_time_type'             => EnumInspectTimeType::class,
            'custom_time_list'              => 'array',
            'remind_rules'                  => 'array',
            'is_remind_group_owner'         => 'bool',
            'enabled'                       => 'bool',
        ];
    }
}
