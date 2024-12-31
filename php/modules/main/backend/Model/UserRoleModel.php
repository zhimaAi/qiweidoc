<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Model;

use Common\DB\BaseModel;

class UserRoleModel extends BaseModel
{
    public function getTableName(): string
    {
        return "main.user_role";
    }

    protected function casts(): array
    {
        return [
            "id" => 'int',
            "created_at" => 'string',
            "updated_at" => 'string',
            "role_name" => 'string',
            "permission_config" => 'array'
        ];
    }

}
