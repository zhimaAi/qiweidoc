<?php

namespace Modules\Main\DTO\Corp;

use Common\DTO\BaseDTO;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Type\StringType;

class SaveCallbackEventTokenBaseDTO extends BaseDTO
{
    public string $callbackEventToken = "";

    public string $callbackEventAesKey = "";

    public function getRules(): iterable
    {
        return [
            'callbackEventToken' => [
                new Required(notPassedMessage: '回调事件token字段不能为空'),
                new StringType(message: '回调事件token格式错误'),
            ],
            'callbackEventAesKey' => [
                new Required(notPassedMessage: '回调事件aes_key字段不能为空'),
                new StringType(message: '回调事件aes_key格式错误'),
                new Length(
                    exactly: 43,
                    notExactlyMessage: '回调事件aes_key字段应该是43字符长度',
                ),
            ],
        ];
    }
}
