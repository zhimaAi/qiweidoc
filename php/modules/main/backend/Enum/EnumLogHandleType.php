<?php

namespace Modules\Main\Enum;

enum EnumLogHandleType:int
{
    case ADD = 1;
    case DELETE = 2;
    public function getLabel(): string
    {
        return match($this) {
            self::ADD => '新增',
            self::DELETE => '删除',
        };
    }

}
