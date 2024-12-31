<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\TimeoutReplySingle\Enum;

enum EnumTimeUnitType: int
{
    case Minute = 1;
    case Hour = 2;
    case Day = 3;
    case Week = 4;

    public function getLabel(): string
    {
        return match($this) {
            self::Minute    => '分钟',
            self::Hour      => '小时',
            self::Day       => '天',
            self::Week      => '周',
        };
    }

    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function transferMinute(EnumTimeUnitType $unit, int $value): int
    {
        return match ($unit) {
            EnumTimeUnitType::Hour => $value * 60,
            EnumTimeUnitType::Day => $value * 60 * 24,
            EnumTimeUnitType::Week => $value * 60 * 24 * 7,
            default => $value,
        };
    }
}
