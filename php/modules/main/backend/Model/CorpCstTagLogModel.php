<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Model;

use Common\DB\BaseModel;
use Modules\Main\Enum\EnumLogHandleType;
use Modules\Main\Enum\EnumLogStatus;
use Modules\Main\Enum\EnumUserRoleType;
use Throwable;

class CorpCstTagLogModel extends BaseModel
{
    const STATUS_UNTREATED = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 2;

    const HANDLE_TYPE_ADD = 1;
    const HANDLE_TYPE_DELETE = 2;

    public function getTableName(): string
    {
        return "main.corp_cst_tag_log";
    }

    protected function casts(): array
    {
        return [
            "id" => "int", // 主键ID
            "created_at" => "string", // 创建时间
            "updated_at" => "string", // 更新时间

            "corp_id" => "string", // 企业ID
            "tag_id" => "string", // 标签ID（单个标签）
            "external_userid" => "string", // 客户external_userid
            "userid" => "string", // 员工user_id
            "source" => "string", // 来源 标识 最大64位
            "batch_number" => "string", // 批次号 批量标识 最大64位
            "handle_time" => "string", // 处理时间
            "time_to_date" => "int", // Ymd格式日期
            "handle_type" => EnumLogHandleType::class, // 处理类型  0：默认数据 1:新增标签 2:删除标签
            "status" =>  EnumLogStatus::class, // 状态 0:未处理，1:处理成功，2:处理失败
            "fail_reason" => "string", // 失败原因
        ];
    }

}
