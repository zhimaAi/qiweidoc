<?php

namespace Modules\TimeoutReplyGroup\Enum;

enum EnumInspectTimeType: int
{
    case Total = 1;
    case WorkerDay = 2;
    case Custom = 3;

    public function getLabel(): string
    {
        return match($this) {
            self::Total =>      '全天质检',
            self::WorkerDay =>  '工作时间质检',
            self::Custom =>     '自定义质检时间',
        };
    }

    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
