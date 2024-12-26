<?php

namespace Modules\TimeoutReplyGroup\Enum;

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
