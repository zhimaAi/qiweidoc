<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\TimeoutReplyGroup\DTO;

use Common\DTO\CommonDTO;
use Modules\TimeoutReplyGroup\Enum\EnumGroupType;
use Modules\TimeoutReplyGroup\Enum\EnumInspectTimeType;
use Modules\TimeoutReplyGroup\Enum\EnumTimeUnitType;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\Count;
use Yiisoft\Validator\Rule\Each;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Nested;
use Yiisoft\Validator\Rule\Number;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Type\BooleanType;
use Yiisoft\Validator\Rule\Type\IntegerType;
use Yiisoft\Validator\Rule\Type\StringType;
use Yiisoft\Validator\ValidationContext;

class RuleDTO extends CommonDTO
{
    public function getRules(): array
    {
        return [
            'name' => [
                new Required(message: '不能为空'),
                new Length(max: 64, greaterThanMaxMessage: '规则名称不能超过64个字符长度')
            ],
            'group_type' => [
                new Required(message: '不能为空'),
                new In(EnumGroupType::getValues(), message: '取值不合法'),
            ],
            'group_chat_id_list' => [
                new Each(
                    new StringType(message: '群聊id列表格式不正确'),
                    incorrectInputMessage: '必须是数组',
                ),
            ],
            'group_staff_userid_list' => [
                new Each(
                    new StringType(message: '员工id列表格式不正确'),
                    incorrectInputMessage: '必须是数组',
                ),
            ],
            'group_keyword_list' => [
                new Nested([
                    'include_list' => [
                        new Each(
                            new StringType(message: '包含关键词列表格式不正确'),
                            incorrectInputMessage: '必须是数组',
                        ),
                    ],
                    'exclude_list' => [
                        new Each(
                            new StringType(message: '排除关键词列表格式不正确'),
                            incorrectInputMessage: '必须是数组',
                        ),
                    ],
                ])
            ],
            'is_remind_group_owner' => [
                new Required(message: '不能为空'),
                new BooleanType(message: '必须是布尔类型'),
            ],
            'inspect_time_type' => [
                new Required(message: '不能为空'),
                new In(EnumInspectTimeType::getValues(), message: '取值不合法'),
            ],
            'custom_time_list' => ReplyRuleDTO::getTimePeriodRules(),
            'remind_rules' => [
                new Required(message: '不能为空'),
                new Count(min: 1, max: 3, greaterThanMaxMessage: '提醒规则不能超过3条'),
                new Each(
                    new Nested([
                        'timeout_unit' => [
                            new Required(message: '不能为空'),
                            new In(EnumTimeUnitType::getValues(), message: '超时单位不合法')
                        ],
                        'timeout_value' => [
                            new Required(message: '超时字段不能为空'),
                            new IntegerType(message: '超时字段必须是整型'),
                            new Number(min: 1, max: 1440, lessThanMinMessage: '超时时间不能少于1分钟', greaterThanMaxMessage: '超时时间不能超过24小时')
                        ],
                        'is_remind_group_member' => [
                            new Required(message: '不能为空'),
                            new BooleanType(message: '必须是布尔类型', skipOnEmpty: true),
                        ],
                        'is_remind_staff_designation' => [
                            new Required(message: '不能为空'),
                            new BooleanType(message: '必须是布尔类型', skipOnEmpty: true),
                        ],
                        'designate_remind_userid_list' => [
                            new Each(
                                new StringType(message: '必须是字符串'),
                            ),
                        ],
                        new Callback(
                            static function (mixed $value, Callback $rule, ValidationContext $context) {
                                $isRemindStaffDesignation = $context->getDataSet()->getAttributeValue('is_remind_staff_designation');
                                $designateRemindUseridList = $context->getDataSet()->getAttributeValue('designate_remind_userid_list');
                                if ($isRemindStaffDesignation && empty($designateRemindUseridList)) {
                                    return (new Result)->addError('提醒指定员工不能为空');
                                }
                                return new Result;
                            },
                            skipOnError: true,
                        )
                    ])
                ),
            ],
            new Callback(
                static function (mixed $value, Callback $rule, ValidationContext $context) {
                    $inspectTimeType = $context->getDataSet()->getAttributeValue('inspect_time_type');
                    $customTimeList = $context->getDataSet()->getAttributeValue('custom_time_list');
                    if ($inspectTimeType == EnumInspectTimeType::Custom && empty($customTimeList)) {
                        return (new Result)->addError('选择自定义质检时，时间段不能为空');
                    }
                    return new Result;
                },
                skipOnError: true,
            ),
        ];
    }
}
