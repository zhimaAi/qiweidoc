<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace Modules\Main\Service;

use LogicException;
use Modules\Main\DTO\UpdateUserInfoBaseDTO;
use Modules\Main\Model\UserModel;

class UserService
{
    public static function updateCurrentUserInfo(UserModel $currentUser, UpdateUserInfoBaseDTO $updateUserInfoDTO): void
    {

        if ($updateUserInfoDTO->password != $updateUserInfoDTO->repeatPassword) {
            throw new LogicException('确认密码不正确');
        }

        $hisUserInfo = UserModel::query()->where(["account" => $updateUserInfoDTO->username])->getOne();

        if (!empty($hisUserInfo) && $hisUserInfo->get("id") != $currentUser->get("id")) {
            throw new LogicException('登陆账号重复');
        }

        $currentUser->update([
            'password' => password_hash($updateUserInfoDTO->password, PASSWORD_DEFAULT, ['cost' => 12]),
            'account' => $updateUserInfoDTO->username,
        ]);
    }
}
