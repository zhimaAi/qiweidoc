<?php

namespace Modules\Main\Enum;

enum EnumCustomerTagType: int
{
    case GROUP = 1;
    case TAG = 2;

    public function getLabel() : string
    {
        return match($this) {
            self::GROUP => 'tag_group',
            self::TAG => 'tag',
        };
    }
}
