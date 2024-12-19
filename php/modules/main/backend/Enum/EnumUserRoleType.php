<?php

namespace Modules\Main\Enum;

enum EnumUserRoleType: int
{
    case NORMAL_STAFF = 1;//普通员工
    case ADMIN = 2;//管理员
    case SUPPER_ADMIN = 3;//超级管理员
    case VISITOR = 4;//游客

}
