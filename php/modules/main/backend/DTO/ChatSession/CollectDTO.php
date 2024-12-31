<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\DTO\ChatSession;

use Common\DTO\BaseDTO;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Type\StringType;

class CollectDTO extends BaseDTO
{
    public ?string $conversationId = null;

    public ?string $collectReason = null;


    public function getRules(): array
    {
        return [
            'conversationId' => [
                new Required(notPassedMessage: '会话存档id不能为空'),
                new StringType(message: '会话存档id不正确'),
            ],
            'collectReason' => [
                new StringType(message: '原因不正确', skipOnEmpty: true),
            ],
        ];
    }
}
