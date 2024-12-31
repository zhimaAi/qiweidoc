<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\TimeoutReplySingle\Model;

use Common\DB\BaseModel;
use Modules\TimeoutReplySingle\Enum\EnumInspectTimeType;
use Modules\TimeoutReplySingle\Enum\EnumTimeUnitType;

class RuleModel extends BaseModel
{
    public function getTableName(): string
    {
        return "timeout_reply_single.rules";
    }

    protected function casts(): array
    {
        return [
            'id'                            => 'int',
            'created_at'                    => 'string',
            'updated_at'                    => 'string',

            'corp_id'                       => 'string',
            'name'                          => 'string',
            'staff_userid_list'             => 'array',
            'inspect_time_type'             => EnumInspectTimeType::class,
            'custom_time_list'              => 'array',
            'timeout_unit'                  => EnumTimeUnitType::class,
            'timeout_value'                 => 'int',
            'is_remind_staff_designation'   => 'bool',
            'is_remind_staff_himself'       => 'bool',
            'designate_remind_userid_list'  => 'array',
            'enabled'                       => 'bool',
        ];
    }
}
