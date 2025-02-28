<?php

namespace Modules\CustomBrand;

use Common\DTO\CommonDTO;
use Yiisoft\Validator\Rule\Each;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Nested;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Type\StringType;

class SettingDto extends CommonDTO
{
    public function getRules(): iterable
    {
        return [
            'title' => [new StringType(skipOnEmpty: true)],
            'logo' => [new StringType(skipOnEmpty: true)],
            'navigation_bar_title' => [new StringType(skipOnEmpty: true)],
            'login_page_title' => [new StringType(skipOnEmpty: true)],
            'login_page_description' => [new StringType(skipOnEmpty: true)],
            'copyright' =>[new StringType(skipOnEmpty: true)],
        ];
    }
}
