<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\UserPermission\Model;


use Common\DB\BaseModel;

/**
 * @author rand
 * @ClassName DetailModel
 * @date 2024/12/1911:53
 * @description
 */
class RolePermissionDetailModel extends BaseModel
{

    public function getTableName(): string
    {
        // TODO: Implement getTableName() method.
        return "user_permission.role_permission_detail";
    }

    protected function casts(): array
    {
        // TODO: Implement casts() method.
        return [
            "created_at" => 'string',
            "updated_at" => 'string',
            "corp_id" => 'string',
            "permission_index" => 'int',
            "server_name" => 'string',
            "description" => 'string',
            "permission_node_title" => 'string',
            "permission_key" => 'string',
            "permission_detail" => 'array',
        ];
    }
}
