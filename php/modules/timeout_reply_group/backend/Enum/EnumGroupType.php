<?php

namespace Modules\TimeoutReplyGroup\Enum;

enum EnumGroupType: int
{
    case ChatIdList = 1;   // 指定群聊
    case StaffIdList = 2;  // 指定员工列表
    case KeywordList = 3;  // 指定关键词

    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
