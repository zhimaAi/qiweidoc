<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace Modules\Main\Service;

use Modules\Main\Model\StaffTagModel;
use Modules\Main\Model\UserModel;
use Throwable;

class TagsService
{

    /**
     * @param UserModel $currentUser
     * @return iterable
     * @throws Throwable
     */
    public static function staff(UserModel $currentUser): iterable
    {
        return StaffTagModel::query()->where(["corp_id" => $currentUser->get('corp_id')])->getAll();
    }
}
