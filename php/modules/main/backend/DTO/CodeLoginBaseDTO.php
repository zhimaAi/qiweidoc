<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\DTO;

use Common\DTO\BaseDTO;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Type\StringType;

class CodeLoginBaseDTO extends BaseDTO
{
    public ?string $corpId = null;

    public ?string $code = null;

    public function getRules(): iterable
    {
        return [
            'corpId' => [
                new Required(notPassedMessage: '企业id不能为空'),
                new StringType(message: '企业id字段不合法'),
            ],
            'code' => [
                new Required(notPassedMessage: '缺少企微授权码'),
                new StringType(message: '企微授权码不合法'),
            ],
        ];
    }
}
