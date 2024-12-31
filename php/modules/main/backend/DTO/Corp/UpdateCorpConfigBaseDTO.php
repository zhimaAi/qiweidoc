<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\DTO\Corp;

use Common\DTO\BaseDTO;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Type\IntegerType;
use Yiisoft\Validator\Rule\Type\StringType;

class UpdateCorpConfigBaseDTO extends BaseDTO
{
    public ?string $agentSecret = null;

    public ?string $chatSecret = null;

    public ?int $chatPublicKeyVersion = null;

    public ?string $callbackEventToken = null;

    public ?string $callbackEventAesKey = null;

    public ?string $corpName = null;

    public ?string $corpLogo = null;


    public function getRules(): array
    {
        return [
            'agentSecret' => [
                new StringType(message: '应用密钥字段格式错误', skipOnEmpty: true),
            ],
            'chatSecret' => [
                new StringType(message: '会话存档密钥字段格式错误', skipOnEmpty: true),
            ],
            'chatPublicKeyVersion' => [
                new IntegerType(message: '公钥版本号字段格式错误', skipOnEmpty: true),
            ],
            'callback_event_token' => [
                new StringType(message: '回调事件token格式错误', skipOnEmpty: true),
            ],
            'callback_event_aes_key' => [
                new StringType(message: '回调事件aes_key格式错误', skipOnEmpty: true),
                new Length(
                    exactly: 43,
                    notExactlyMessage: '回调事件aes_key字段应该是43字符长度',
                    skipOnEmpty: true,
                ),
            ],
            'corpName' => [
                new StringType(message: '企业名称字段格式错误', skipOnEmpty: true),
                new Length(
                    max: 12,
                    greaterThanMaxMessage: '企业名称最大长度为12个字',
                    skipOnEmpty: true,
                ),
            ],
            'corpLogo' => [
                new StringType(message: '企业logo格式错误', skipOnEmpty: true),
            ],
        ];
    }
}
