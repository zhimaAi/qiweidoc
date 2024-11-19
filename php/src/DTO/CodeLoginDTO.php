<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\DTO;

use App\Libraries\Core\BaseDTO;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Type\StringType;
use Yiisoft\Validator\RulesProviderInterface;

class CodeLoginDTO extends BaseDTO implements RulesProviderInterface
{
    public ?string $corpId = null;

    public ?string $code = null;

    public function getRules(): iterable
    {
        return [
            'corpId' => [
                new Required(message: '企业id不能为空'),
                new StringType(message: '企业id字段不合法'),
            ],
            'code' => [
                new Required(message: '缺少企微授权码'),
                new StringType(message: '企微授权码不合法'),
            ],
        ];
    }
}
