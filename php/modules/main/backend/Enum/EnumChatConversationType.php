<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

/**
 * 所有的枚举类型在这里定义
 */

namespace Modules\Main\Enum;

/**
 * 聊天类别
 */
enum EnumChatConversationType: int
{
    case Single = 1;    //单聊
    case Group = 2;     //群聊
    case Internal = 3;  //员工内部聊天
}
