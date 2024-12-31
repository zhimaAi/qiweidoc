<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\DTO\Corp;

use Common\DTO\BaseDTO;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Type\StringType;

class SaveCorpInfoBaseDTO extends BaseDTO
{
    public ?string $chatPublicKey = null;

    public ?string $chatPrivateKey = null;

    public ?string $chatSecret = null;

    public ?int $chatPublicKeyVersion = null;

    public function getRules(): iterable
    {
        return [
            'chatPublicKey' => [
                new Required(notPassedMessage: '企业公钥不能为空'),
                new StringType(message: '企业公钥字段不合法'),
            ],
            'chatPrivateKey' => [
                new Required(notPassedMessage: '企业私钥不能为空'),
                new StringType(message: '企业私钥字段不合法'),
            ],
            'chatSecret' => [
                new Required(notPassedMessage: '会话存档密钥不能为空'),
                new StringType(message: '会话存档密钥字段不合法'),
            ],
            'chatPublicKeyVersion' => [
                new Required(notPassedMessage: '公钥版本号不能为空'),
                new Integer(notNumberMessage: '公钥版本号字段不合法'),
            ],
        ];
    }
}
