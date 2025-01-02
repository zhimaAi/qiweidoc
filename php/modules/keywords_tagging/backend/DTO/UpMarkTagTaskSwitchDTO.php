<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\KeywordsTagging\DTO;

use Common\DTO\CommonDTO;

use Modules\KeywordsTagging\Enum\EnumCheckType;
use Modules\KeywordsTagging\Enum\EnumDelStatus;
use Modules\KeywordsTagging\Enum\EnumSwitch;
use Modules\KeywordsTagging\Enum\EnumToggleInterval;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\Count;
use Yiisoft\Validator\Rule\Each;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Nested;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Type\StringType;
use Yiisoft\Validator\ValidationContext;

/**
 * @details 根据Id修改状态检测
 * @author ivan
 * @date 2024/12/23 15:12
 * Class UpMarkTagTaskSwitchDTO
 */
class UpMarkTagTaskSwitchDTO extends CommonDTO
{
    public function getRules(): array
    {
        return [
            'id'=>[
                new Required(message: '任务Id不能为空'),
                new Integer(min:1,incorrectInputMessage:'请传递正确的任务Id',lessThanMinMessage: '任务id不能小于1', skipOnEmpty: true),
            ],
            'switch' => [
                new In(EnumSwitch::getValues(), message: '任务开关不合法',skipOnEmpty: true)
            ],
            'del_status' => [
                new In(EnumDelStatus::getValues(), message: '删除状态不合法',skipOnEmpty: true)
            ],
        ];
    }
}
