<?php


namespace Modules\Main\Enum;

/**
 * 聊天类别
 */
enum EnumChatCollectStatus: int
{
    case Collect = 1;    //收藏
    case NoCollect = 0;     //未收藏
}
