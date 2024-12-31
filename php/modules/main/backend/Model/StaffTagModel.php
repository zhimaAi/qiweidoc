<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Model;

use Common\DB\BaseModel;

class StaffTagModel extends BaseModel
{
    public function getTableName(): string
    {
        return "main.staff_tag";
    }

    protected function casts(): array
    {
        return [
            "id" => 'int',
            "created_at" => 'string',
            "updated_at" => 'string',
            "tag_id" => 'int',
            "tag_name" => 'string',
            "corp_id" => 'string',
        ];
    }

}
