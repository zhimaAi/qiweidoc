<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Services;

use App\DTO\UpdateUserInfoDTO;
use App\Models\UserModel;

class UserService
{
    public static function updateCurrentUserInfo(UserModel $currentUser, UpdateUserInfoDTO $updateUserInfoDTO): void
    {
        $currentUser->update([
            'password' => password_hash($updateUserInfoDTO->password, PASSWORD_DEFAULT, ['cost' => 12]),
        ]);
    }
}
