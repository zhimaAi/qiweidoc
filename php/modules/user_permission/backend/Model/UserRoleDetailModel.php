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
class UserRoleDetailModel extends BaseModel
{

    public function getTableName(): string
    {
        return "user_permission.user_role_detail";
    }

    protected function casts(): array
    {
        return [
            "created_at" => 'string',
            "updated_at" => 'string',
            "corp_id" => 'string',
            "other_role_id" => 'int',
            "staff_userid" => 'string',
        ];
    }
}
