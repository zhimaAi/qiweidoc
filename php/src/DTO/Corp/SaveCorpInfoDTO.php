<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\DTO\Corp;

use App\Libraries\Core\BaseDTO;
use Symfony\Contracts\Service\Attribute\Required;
use Yiisoft\Validator\Rule\Type\StringType;

class SaveCorpInfoDTO extends BaseDTO
{
    public ?string $chatPublicKey = null;

    public ?string $chatPrivateKey = null;

    public ?string $chatSecret = null;

    public ?int $chatPublicKeyVersion = null;

    public function getRules(): iterable
    {
        return [
            'chatPublicKey' => [
                new Required(message: '企业公钥不能为空'),
                new StringType(message: '企业公钥字段不合法'),
            ],
            'chatPrivateKey' => [
                new Required(message: '企业私钥不能为空'),
                new StringType(message: '企业私钥字段不合法'),
            ],
            'chatSecret' => [
                new Required(message: '会话存档密钥不能为空'),
                new StringType(message: '会话存档密钥字段不合法'),
            ],
            'chatPublicKeyVersion' => [
                new Required(message: '公钥版本号不能为空'),
                new StringType(message: '公钥版本号字段不合法'),
            ],
        ];
    }
}
