<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\KeywordsTagging\Model;


use Common\DB\BaseModel;
use Modules\KeywordsTagging\Enum\EnumCheckChatType;
use Modules\KeywordsTagging\Enum\EnumCheckType;
use Modules\KeywordsTagging\Enum\EnumDelStatus;
use Modules\KeywordsTagging\Enum\EnumSwitch;

/**
 * @description 关键词打标签任务表
 * @author ivan
 */
class MarkTagTaskModel extends BaseModel
{

    public function getTableName(): string
    {
        return "keywords_tagging.mark_tag_task";
    }

    protected function casts(): array
    {
        return [
            "id" => "int", // 主键ID
            "created_at" => "string", // 创建时间
            "updated_at" => "string", // 更新时间
            "corp_id" => "string", // 所属企业微信id
            "check_type" => EnumCheckType::class, // 生效用户 1:仅客户 2:仅员工 3:客户和员工
            "check_chat_type" => EnumCheckChatType::class, // 生效场景 1.单聊 2.群聊
            "name" => "string", // 任务名称

            "staff_userid_list" => "array", // 员工user_id数组
            "partial_match" => "array", // 模糊匹配关键词数组
            "full_match" => "array", // 精准匹配关键词数组

            "rules_list" => "array", // 规则列表数组
            "switch" => EnumSwitch::class, // 开关 0 关 1 开
            "del_status" => EnumDelStatus::class, // 删除标识 0 未删除 1 删除
        ];
    }
}
