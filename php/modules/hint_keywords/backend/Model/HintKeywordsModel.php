<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\HintKeywords\Model;


use Common\DB\BaseModel;

/**
 * @author rand
 * @ClassName HintKeywordsModel
 * @date 2024/12/314:47
 * @description
 */
class HintKeywordsModel extends BaseModel
{

    public function getTableName(): string
    {
        return "hint_keywords.hint_keywords";
    }

    protected function casts(): array
    {
        return [
            "id" => 'int',
            "created_at" => 'string',
            "updated_at" => 'string',
            "corp_id" => 'string',
            "group_name" => 'string',
            "keywords" => 'array',
            "create_user_id" => 'string',
        ];
    }
}
