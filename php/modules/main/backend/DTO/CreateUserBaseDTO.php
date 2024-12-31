<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\DTO;

use Common\DTO\BaseDTO;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Type\StringType;

class CreateUserBaseDTO extends BaseDTO
{
    public ?int $id = 0;

    public ?string $account = null;

    public ?string $password = null;

    public ?string $verifyPassword = null;

    public ?int $expTime = 0;

    public ?string $description = null;

    public function getRules(): array
    {
        return [
            'account' => [
                new Required(notPassedMessage: '用户名不能为空'),
                new StringType(message: '用户名不合法'),
                new Length(min: 6, max: 32, greaterThanMaxMessage: '用户名长度范围6到32位'),
            ]
        ];
    }
}
