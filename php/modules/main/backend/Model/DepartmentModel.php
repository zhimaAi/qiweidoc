<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Model;

use Common\DB\BaseModel;

class DepartmentModel extends BaseModel
{
    public function getTableName(): string
    {
        return "main.department";
    }

    protected function casts(): array
    {
        return [
            "id" => 'int',
            "created_at" => 'string',
            "updated_at" => 'string',
            "department_id" => 'int',
            "corp_id" => 'string',
            "order" => 'int',
            "parent_id" => 'int',
            "name" => 'string',
            'department_leader' => 'array',
        ];
    }
}
