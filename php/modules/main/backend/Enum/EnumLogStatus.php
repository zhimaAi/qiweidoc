<?php

namespace Modules\Main\Enum;

enum EnumLogStatus:int
{

    case SUCCESS = 1;
    case FAIL = 2;

    public function getLabel() : string
    {
        return match($this) {
            self::SUCCESS => '操作成功',
            self::FAIL => '操作失败',
        };
    }
}
