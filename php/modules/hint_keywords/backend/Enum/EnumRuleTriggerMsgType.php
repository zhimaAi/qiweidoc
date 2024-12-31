<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

/**
 * 所有的枚举类型在这里定义
 */

namespace Modules\HintKeywords\Enum;

/**
 * 触发消息类型
 */
enum EnumRuleTriggerMsgType: string
{
    case Text = "text";    //文本
    case Link = "link";     //链接
    case Weapp = "weapp";  //小程序
    case Card = "card";  //卡片
    case LinkText = "link_text";  //包含链接的文本
    case QrCode = "qr_code";  //二维码图片
}
