<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Models;

use App\Libraries\Core\DB\BaseModel;

class StaffTagModel extends BaseModel
{
    public function getTableName(): string
    {
        return "staff_tag";
    }

    public function getPrimaryKeys(): string | array
    {
        return "id";
    }

    protected function casts(): array
    {
        return [];
    }

}
