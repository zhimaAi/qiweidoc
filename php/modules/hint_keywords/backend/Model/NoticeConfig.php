<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\HintKeywords\Model;


use Common\DB\BaseModel;

/**
 * @author rand
 * @ClassName NoticeConfig
 * @date 2024/12/315:49
 * @description
 */
class NoticeConfig extends BaseModel
{

    public function getTableName(): string
    {
        return "hint_keywords.notice_config";
    }

    protected function casts(): array
    {
        return [
            "id" => 'int',
            "created_at" => 'string',
            "updated_at" => 'string',
            "corp_id" => 'string',
            "notice_switch" => 'int',
            "app_notice_switch" => 'int',
            "app_notice_userid" => 'array',
            "wechat_notice_switch" => 'int',
            "wechat_notice_hook" => 'string',
            "wechat_notice_type" => 'int',
            "wechat_notice_user" => 'array',
            "dingtalk_notice_switch" => 'int',
            "dingtalk_notice_hook" => 'string',
            "dingtalk_notice_secret" => 'string',
            "dingtalk_notice_type" => 'string',
            "dingtalk_notice_user" => 'array',
            "statistics_msg_time" => 'string',
        ];
    }
}
