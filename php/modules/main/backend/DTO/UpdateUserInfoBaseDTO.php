<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\DTO;

use Common\DTO\BaseDTO;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Type\StringType;

class UpdateUserInfoBaseDTO extends BaseDTO
{
    public ?string $username = null;

    public ?string $password = null;

    public ?string $repeatPassword = null;

    public function getRules(): array
    {
        return [
            'username' => [
                new Required(notPassedMessage: '用户名不能为空'),
                new StringType(message: '用户名不合法'),
                new Length(max: 32, greaterThanMaxMessage: '用户名不能超过32个字'),
            ],
            'password' => [
                new StringType(message: '密码不合法', skipOnEmpty: true),
                new Length(min: 6, max: 32, notExactlyMessage: '密码长度范围6到32位', skipOnEmpty: true),
            ],
            'repeatPassword' => [
                new StringType(message: '密码不合法', skipOnEmpty: true),
                new Length(min: 6, max: 32, notExactlyMessage: '密码长度范围6到32位', skipOnEmpty: true),
            ],
        ];
    }
}
