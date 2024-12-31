<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\TimeoutReplySingle\DTO;

use Common\DTO\CommonDTO;
use Modules\TimeoutReplySingle\Model\ReplyRuleModel;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\Each;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Nested;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Type\BooleanType;
use Yiisoft\Validator\Rule\Type\StringType;
use Yiisoft\Validator\ValidationContext;

class ReplyRuleDTO extends CommonDTO
{
    public static function getTimePeriodRules()
    {
        return [
            new Each(
                new Nested([
                    'week_day_list' => [
                        new Required('不能为空'),
                        new Each(
                            new Integer(
                                min: 0,
                                max: 6,
                                notNumberMessage: '必须是整数',
                                lessThanMinMessage: '每一项的值必须在0到6之间',
                                greaterThanMaxMessage: '每一项的值必须在0到6之间',
                            ),
                            incorrectInputMessage: '必须是数组',
                        ),
                    ],
                    'time_period_list' => [
                        new Required(message: '不能为空'),
                        new Each(
                            new Callback(
                                static function (mixed $value, Callback $rule, ValidationContext $context): Result {
                                    if (empty($value['start']) || empty($value['end'])) {
                                        return (new Result)->addError('时间区间格式必须有start和end');
                                    }
                                    if (!ReplyRuleModel::isValidTime($value['start'])) {
                                        return (new Result)->addError('start字段格式不正确');
                                    }
                                    if (!ReplyRuleModel::isValidTime($value['end'])) {
                                        return (new Result)->addError('end字段格式不正确');
                                    }
                                    if ($value['start'] >= $value['end']) {
                                        return (new Result)->addError('start时间必须小于end时间');
                                    }
                                    return new Result;
                                }),
                        ),
                        new Callback(
                            static function (mixed $value, Callback $rule, ValidationContext $context): Result {
                                if (ReplyRuleModel::hasOverlappingPeriods($value)) {
                                    return (new Result)->addError("时间区间不能重叠");
                                }
                                return new Result;
                                },
                                skipOnError: true,
                            ),
                    ],
                ]),
            ),
        ];
    }

    public function getRules(): array
    {
        return [
            'filter_full_match_word_list' => [
                new Each(
                    new StringType(message: '必须是字符串'),
                    incorrectInputMessage: '必须是数组',
                ),
            ],
            'filter_half_match_word_list' => [
                new Each(
                    new StringType(message: '必须是字符串'),
                    incorrectInputMessage: '必须是数组',
                )
            ],
            'include_image_msg' => [
                new Required(message: '不能为空'),
                new BooleanType(message: '必须是布尔类型'),
            ],
            'include_emoji_msg' => [
                new Required(message: '不能为空'),
                new BooleanType(message: '必须是布尔类型'),
            ],
            'include_emoticons_msg' => [
                new Required(message: '不能为空'),
                new BooleanType(message: '必须是布尔类型'),
            ],
            'working_hours' => array_merge(
                [new Required(message: '不能为空')],
                self::getTimePeriodRules(),
            ),
            new Callback(
                static function (mixed $value, Callback $rule, ValidationContext $context): Result {
                    $workingHours = $context->getDataSet()->getAttributeValue('working_hours');
                    $weekDays = [];
                    foreach ($workingHours as $row) {
                        if (array_intersect($weekDays, $row['week_day_list'])) {
                            return (new Result())->addError('工作日有重复');
                        }
                        $weekDays = array_merge($weekDays, $row['week_day_list']);
                    }
                    return new Result;
                },
                skipOnError: true,
            ),
        ];
    }
}
