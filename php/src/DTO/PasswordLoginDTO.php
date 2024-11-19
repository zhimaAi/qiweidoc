<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\DTO;

use App\Libraries\Core\BaseDTO;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Type\StringType;
use Yiisoft\Validator\RulesProviderInterface;

class PasswordLoginDTO extends BaseDTO implements RulesProviderInterface
{
    public ?string $username = '';

    public ?string $password = '';

    public function getRules(): array
    {
        return [
            'username' => [
                new Required(message: '用户名不能为空'),
                new StringType(message: '用户名不合法'),
            ],
            'password' => [
                new Required(message: '密码不能为空'),
                new StringType(message: '密码不合法'),
            ],
        ];
    }

}
