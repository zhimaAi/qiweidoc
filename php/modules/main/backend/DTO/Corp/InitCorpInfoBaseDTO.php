<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\DTO\Corp;

use Common\DTO\BaseDTO;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Number;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Type\IntegerType;
use Yiisoft\Validator\Rule\Type\StringType;

class InitCorpInfoBaseDTO extends BaseDTO
{
    public ?string $corpId = null;

    public ?int $agentId = null;

    public ?string $secret = null;

    public ?string $verifyDomainFileName = null;

    public ?string $verifyDomainFileContent = null;

    public function getRules(): iterable
    {
        return [
            'corpId' => [
                new Required(notPassedMessage: '企业id不能为空'),
                new StringType(message: '企业id不合法'),
                new Length(max: 32, greaterThanMaxMessage: '企业id长度不合法'),
            ],
            'agentId' => [
                new Required(notPassedMessage: '应用id不能为空'),
                new IntegerType(message: '应用id不合法'),
                new Number(max: PHP_INT_MAX, greaterThanMaxMessage: '应用id不合法'),
            ],
            'secret' => [
                new Required(notPassedMessage: '密钥不能为空'),
                new StringType(message: '密钥不合法'),
                new Length(max: 64, greaterThanMaxMessage: '密钥长度不合法'),
            ],
            'verifyDomainFileName' => [
                new Callback(
                    static function (mixed $value): Result {
                        if (empty($value)) {
                            return (new Result())->addError('验证文件的名称不能为空');
                        }
                        if (!is_string($value)) {
                            return (new Result())->addError("验证文件的名称不合法");
                        }
                        if (!str_starts_with($value, 'WW_verify_') || !str_ends_with($value, '.txt')) {
                            return (new Result())->addError('验证文件的名称必须是WW_verify_开头和.txt结尾');
                        }

                        return new Result();
                    },
                ),
            ],
            'verifyDomainFileContent' => [
                new Required(notPassedMessage: '验证文件的内容不能为空'),
                new StringType(message: '验证文件的内容不合法'),
            ],
        ];
    }
}
