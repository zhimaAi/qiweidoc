<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\KeywordsTagging\Enum;

use DateTime;
use Exception;

enum EnumToggleInterval: int
{
    case Day = 1;
    case Week = 2;
    case Month = 3;

    public function getLabel(): string
    {
        return match($this) {
            self::Day => '天',
            self::Week => '周',
            self::Month => '月',
        };
    }

    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * 检查消息时间是否在toggleInterval范围内
     * @param $msg_time
     * @return bool
     */
    public function CheckTimeFrame($msg_time): bool
    {
        // 将时间字符串转换为DateTime对象
        try {
            $time = new DateTime($msg_time);
        } catch (Exception $e) {
            return false;
        }
        // 获取当前时间的DateTime对象
        $now = new DateTime();
        // 根据toggleInterval的值判断时间是否在当前时间范围内
        return match ($this) {
            self::Day => $time->format('Y-m-d') === $now->format('Y-m-d'),
            self::Week => $time->format('W') === $now->format('W') && $time->format('Y') === $now->format('Y'),
            self::Month => $time->format('Y-m') === $now->format('Y-m'),
            default => false,
        };
    }

    /**
     * 获取toggleInterval范围内的时间范围
     * @param $msg_time
     * @return array|string[]
     * @throws \DateMalformedStringException
     * @author ivan
     * @date 2024/12/28 11:49
     */
    public function getTimeFrameBetween($msg_time): array
    {
        try {
            $time = new DateTime($msg_time);
        } catch (Exception $e) {
            return [
                'start' => date('Y-m-d'). ' 00:00:00',
                'end' => date('Y-m-d').  ' 23:59:59',
            ];
        }
        $start = clone $time; // 克隆日期对象
        $end= clone $time; // 克隆日期对象

        return match ($this) {
            self::Week =>[
                'start' => $start->modify('this week')->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                'end' => $start->modify('this week')->modify('+6 days')->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
            ],
            self::Month => [
                'start' =>  $start->format('Y-m-01') . ' 00:00:00',
                'end' => $end->format('Y-m-t') . ' 23:59:59',
            ],
            default => [
                'start' => $start->format('Y-m-d') . ' 00:00:00',
                'end' => $end->format('Y-m-d') . ' 23:59:59',
            ],
        };
    }
}
