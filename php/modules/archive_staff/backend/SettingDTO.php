<?php

namespace Modules\ArchiveStaff;

use Common\DTO\CommonDTO;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Number;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Type\BooleanType;
use Yiisoft\Validator\Rule\Type\IntegerType;
use Yiisoft\Validator\Rule\Type\StringType;
use Yiisoft\Validator\ValidationContext;

class SettingDTO extends CommonDTO
{
    public function getRules(): iterable
    {
        return [
            'is_staff_designated' => [new Required(), new In([0, 1])],
            'max_staff_num' => [new Number(min: 1, skipOnEmpty: true)],
            'enable_voice_play' => [new Required(), new In([0, 1])],
            new Callback(
                static function (mixed $value, Callback $rule, ValidationContext $context) {
                    $isStaffDesignated = $context->getDataSet()->getAttributeValue('is_staff_designated');
                    $maxStaffNum = $context->getDataSet()->getAttributeValue('max_staff_num');
                    if ($isStaffDesignated && empty($maxStaffNum)) {
                        return (new Result)->addError("指定存档员工时必须填写员工数量");
                    }
                    return new Result;
                },
                skipOnError: true,
            ),
        ];
    }
}
