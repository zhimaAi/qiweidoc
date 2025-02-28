<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\UserPermission\Model;

use Common\DB\BaseModel;


/**
 * Notes: 角色权限配置
 * User: rand
 * Date: 2024/12/30 18:23
 */
class OtherRoleModel extends BaseModel
{

    public function getTableName(): string
    {
        return "user_permission.other_role";
    }

    protected function casts(): array
    {
        return [
            "id" => 'int',
            "created_at" => 'string',
            "updated_at" => 'string',
            "corp_id" => 'string',
            "role_name" => 'string',
            "permission_config" => 'array',
        ];
    }
}
