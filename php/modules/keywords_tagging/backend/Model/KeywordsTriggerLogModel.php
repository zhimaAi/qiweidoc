<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\KeywordsTagging\Model;


use Common\DB\BaseModel;

/**
 * @description 聊天关键词触发日志表
 * @author ivan
 *
 */
class KeywordsTriggerLogModel extends BaseModel
{

    public function getTableName(): string
    {
        return "keywords_tagging.keywords_trigger_log";
    }

    protected function casts(): array
    {
        return [
            "id" => "int", // 主键ID
            "created_at" => "string", // 创建时间
            "updated_at" => "string", // 更新时间
            "corp_id" => "string", // 所属企业微信id
            "staff_userid" => "string", // 员工user_id
            "external_userid" => "string", // 客户external_userid
            "task_id" => "int", // 任务id
            "keyword" => "string", // 匹配关键词
            "msg_id" => "string", // 企业微信消息唯一id
            "msg_time" => "string" // 消息发出时间
        ];
    }
}
