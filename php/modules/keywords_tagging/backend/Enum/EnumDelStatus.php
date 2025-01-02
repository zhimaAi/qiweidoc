<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\KeywordsTagging\Enum;

enum EnumDelStatus: int
{
    case Normal = 0;
    case DELETED = 1;

    public function getLabel(): string
    {
        return match($this) {
            self::Normal => '正常',
            self::DELETED => '已删除',
        };
    }

    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
