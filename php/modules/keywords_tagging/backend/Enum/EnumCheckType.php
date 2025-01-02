<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\KeywordsTagging\Enum;

enum EnumCheckType: int
{
    case Custom = 1;
    case Staff = 2;
    case CustomAndStaff = 3;

    public function getLabel(): string
    {
        return match($this) {
            self::Custom => '仅客户',
            self::Staff => '仅员工',
            self::CustomAndStaff => '客户和员工',
        };
    }

    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
