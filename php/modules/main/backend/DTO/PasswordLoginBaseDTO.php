<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\DTO;

use Common\DTO\BaseDTO;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Type\StringType;

class PasswordLoginBaseDTO extends BaseDTO
{
    public ?string $username = '';

    public ?string $password = '';

    public function getRules(): array
    {
        return [
            'username' => [
                new Required(notPassedMessage: '用户名不能为空'),
                new StringType(message: '用户名不合法'),
            ],
            'password' => [
                new Required(notPassedMessage: '密码不能为空'),
                new StringType(message: '密码不合法'),
            ],
        ];
    }

}
