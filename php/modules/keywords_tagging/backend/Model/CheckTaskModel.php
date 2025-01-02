<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\KeywordsTagging\Model;


use Common\DB\BaseModel;
use Modules\KeywordsTagging\Enum\EnumSwitch;


/**
 * @description 检测任务表
 * @author ivan
 */
class CheckTaskModel extends BaseModel
{

    public function getTableName(): string
    {
        return "keywords_tagging.check_task";
    }

    protected function casts(): array
    {
        return [
            "id" => 'int',
            "created_at" => 'string',
            "updated_at" => 'string',
            "corp_id" => 'string',
            "switch" => EnumSwitch::class,
            "last_msg_time" => 'string',
            "last_msg_id" => 'string',
        ];
    }
}
