<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\HintKeywords\Model;


use Common\DB\BaseModel;
use Modules\HintKeywords\Enum\EnumRuleTriggerMsgType;

/**
 * @author rand
 * @ClassName DetailModel
 * @date 2024/12/316:28
 * @description
 */
class DetailModel extends BaseModel
{

    protected function isAutoIncrementPK(): bool
    {
        return false;
    }

    protected array | string $primaryKeys = 'msg_id';

    public function getTableName(): string
    {
        return "hint_keywords.detail";
    }

    protected function casts(): array
    {
        return [
            "created_at" => 'string',
            "updated_at" => 'string',
            "corp_id" => 'string',
            "hint_type" => 'int',
            "from_user_id" => 'string',
            "from_role" => 'int',
            "to_role" => 'int',
            "to_user_id" => 'string',
            "msg_type" => 'string',
            "target_msg_type" => EnumRuleTriggerMsgType::class,
            "msg_id" => 'string',
            "msg_time" => 'string',
            "msg_content" => 'string',
            "conversation_type" => 'int',
            "conversation_id" => 'string',
            "rule_id" => 'int',
            "hint_keyword" => 'string',
        ];
    }
}
