<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\KeywordsTagging\Enum;

enum EnumSwitch: int
{
    case SwitchOn = 1;
    case SwitchOff = 0;

    public function getLabel(): string
    {
        return match($this) {
            self::SwitchOn => '开启',
            self::SwitchOff => '关闭',
        };
    }

    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
