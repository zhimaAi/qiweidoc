<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\TimeoutReplySingle\Model;

use Carbon\Carbon;
use Common\DB\BaseModel;
use Common\Yii;
use Modules\TimeoutReplySingle\DTO\ReplyRuleDTO;
use Yiisoft\Validator\Validator;

class ReplyRuleModel extends BaseModel
{
    public function getTableName(): string
    {
        return "timeout_reply_single.reply_rule";
    }

    protected function casts(): array
    {
        return [
            'id'                => 'int',
            'created_at'        => 'string',
            'updated_at'        => 'string',

            'corp_id'                       => 'string',
            'filter_full_match_word_list'   => 'array',
            'filter_half_match_word_list'   => 'array',
            'include_image_msg'             => 'boolean',
            'include_emoji_msg'             => 'boolean',
            'include_emoticons_msg'         => 'boolean',
            'working_hours'                 => 'array',
        ];
    }

    public static function getDefaultData()
    {
        return [
            'filter_full_match_word_list' => ['好的谢谢', '好的', '谢谢'],
            'filter_half_match_word_list' => [],
            'include_image_msg' => false,
            'include_emoji_msg' => false,
            'include_emoticons_msg' => false,
            'working_hours' => [
                [
                    'week_day_list' => [0, 1, 2, 3, 4, 5, 6],
                    'time_period_list' => [
                        ['start' => '09:00', 'end' => '12:00'],
                        ['start' => '13:30', 'end' => '18:00'],
                    ],
                ]
            ],
        ];
    }

    /**
     * 检查时间格式，必须是：11:12 这种
     */
    public static function isValidTime($time)
    {
        return preg_match('/^(?:[01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time) === 1;
    }

    /**
     * 检查时间段中是否有重叠的时间段
     * 比如：
     * [
     *      ['start' => '09:00', 'end' => '12:00'],
     *      ['start' => '10:00', 'end' => '11:00'],
     * ]
     * 就不合法
     */
    public static function hasOverlappingPeriods(array $timePeriods): bool
    {
        usort($timePeriods, function ($a, $b) {
            return $a['start'] <=> $b['start'];
        });

        $lastEnd = null;
        foreach ($timePeriods as $period) {
            if ($lastEnd !== null && $period['start'] < $lastEnd) {
                return true;
            }
            $lastEnd = $period['end'];
        }
        return false;
    }

    /**
     * 检查是否在时间段内
     */
    public static function withinTheTimePeriod(array $periods, Carbon $time): bool
    {
        $rule = ReplyRuleDTO::getTimePeriodRules();
        $timePeriodDto = (new Validator())->validate($periods, $rule);
        if (! $timePeriodDto->isValid()) {
            Yii::logger()->warning("时间段格式验证失败", ['detail' => $timePeriodDto->getErrorMessages()]);
            return false;
        }

        // 数据已经过验证，下面不需要判断数组越界
        foreach ($periods as $period) {
            // 不在规定的日期内的过滤掉
            if (!in_array($time->dayOfWeek(), $period['week_day_list'])) {
                continue;
            }
            // 检查是否在规定时间段内
            foreach ($period['time_period_list'] as $timePeriod) {
                if ($time->format('H:i') >= $timePeriod['start'] && $time->format('H:i') <= $timePeriod['end']) {
                    return true;
                }
            }
        }
        return false;
    }
}
