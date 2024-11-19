<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\DTO\Corp;

use App\Libraries\Core\BaseDTO;
use Yiisoft\Validator\Rule\Type\IntegerType;
use Yiisoft\Validator\Rule\Type\StringType;

class UpdateCorpConfigDTO extends BaseDTO
{
    public ?string $agentSecret = null;

    public ?string $chatSecret = null;

    public ?int $chatPublicKeyVersion = null;

    public function getRules(): array
    {
        return [
            'agentSecret' => [
                new StringType(message: '应用密钥字段格式错误'),
            ],
            'chatSecret' => [
                new StringType(message: '会话存档密钥字段格式错误'),
            ],
            'chatPublicKeyVersion' => [
                new IntegerType(message: '公钥版本号字段格式错误'),
            ],
        ];
    }
}
