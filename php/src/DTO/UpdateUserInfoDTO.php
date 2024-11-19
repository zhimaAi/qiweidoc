<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\DTO;

use App\Libraries\Core\BaseDTO;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Type\StringType;
use Yiisoft\Validator\RulesProviderInterface;
use Yiisoft\Validator\ValidationContext;

class UpdateUserInfoDTO extends BaseDTO implements RulesProviderInterface
{
    public ?string $username = null;

    public ?string $password = null;

    public ?string $repeatPassword = null;

    public function getRules(): array
    {
        return [
            'username' => [
                new Required(message: '用户名不能为空'),
                new StringType(message: '用户名不合法'),
                new Length(max: 32, greaterThanMaxMessage: '用户名不能超过32个字'),
            ],
            'password' => [
                new StringType(message: '密码不合法', skipOnEmpty: true),
                new Length(min: 6, max: 32, lessThanMinMessage: '密码不能少于6位', greaterThanMaxMessage: '密码不能超过32位', skipOnEmpty: true),
            ],
            'repeatPassword' => [
                new Callback(
                    static function (mixed $value, Callback $rule, ValidationContext $context): Result {
                        $result = new Result();
                        $password = $context->getDataSet()['password'] ?? null;

                        // 如果设置了密码，则重复密码必填
                        if (! empty($password) && empty($value)) {
                            $result->addError('请再次输入密码');

                            return $result;
                        }

                        // 如果两个密码都填写了，则必须相同
                        if (! empty($password) && ! empty($value) && $password !== $value) {
                            $result->addError('两次输入的密码不一致');
                        }

                        return $result;
                    },
                    skipOnEmpty: true,
                ),
            ],
        ];
    }
}
