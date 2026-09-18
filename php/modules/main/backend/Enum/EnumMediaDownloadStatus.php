<?php

namespace Modules\Main\Enum;

/**
 * 会话媒体从企微获取的流程状态。
 *
 * 该状态只描述是否曾成功获取媒体，不表示对象当前是否仍存在于本地或云存储。
 */
enum EnumMediaDownloadStatus: int
{
    case NotRequired = 0;
    case Pending = 1;
    case Processing = 2;
    case Completed = 3;
    case Exhausted = 4;
    case Unknown = 5;
}
