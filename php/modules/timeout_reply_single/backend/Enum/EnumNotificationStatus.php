<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\TimeoutReplySingle\Enum;

enum EnumNotificationStatus: int
{
    case Succeed = 1;
    case Failed = 2;

    public function getLabel(): string
    {
        return match($this) {
            self::Succeed   => '发送成功',
            self::Failed    => '发送失败',
        };
    }
}
