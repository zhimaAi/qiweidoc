<?php

namespace Modules\Main\DTO;

use Common\DTO\CommonDTO;
use Yiisoft\Validator\Rule\Each;
use Yiisoft\Validator\Rule\Nested;
use Yiisoft\Validator\Rule\Type\StringType;

class UpdateArchiveStaffDTO extends CommonDTO
{
    public function getRules(): iterable
    {
        return [
            'staff_userid_list' => [
                new Each(new StringType()),
            ],
        ];
    }
}
