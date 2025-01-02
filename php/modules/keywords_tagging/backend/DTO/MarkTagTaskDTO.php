<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\KeywordsTagging\DTO;

use Common\DTO\CommonDTO;

use Modules\KeywordsTagging\Enum\EnumCheckType;
use Modules\KeywordsTagging\Enum\EnumSwitch;
use Modules\KeywordsTagging\Enum\EnumToggleInterval;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\Count;
use Yiisoft\Validator\Rule\Each;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Nested;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Type\StringType;
use Yiisoft\Validator\ValidationContext;

/**
 * @details 保存任务检测
 * @author ivan
 * @date 2024/12/23 15:12
 * Class MarkTagTaskDTO
 */
class MarkTagTaskDTO extends CommonDTO
{
    public function getRules(): array
    {
        return [

            'id'=>[
                new Integer( min:1, incorrectInputMessage:'任务id必须是正整数',lessThanMinMessage: '任务id不能小于1',skipOnEmpty: true),
            ],

            'name' => [
                new Required(message: '规则名称不能为空'),
                new Length(max: 64, greaterThanMaxMessage: '规则名称不能超过64个字符长度')
            ],
            'staff_userid_list' => [
                new Required(message: '生效员工不能为空'),
                new Each(
                    new StringType(message: '生效员工列表格式不正确'),
                    incorrectInputMessage: '生效员工必须是数组',
                ),
            ],

            'partial_match' => [
                new Each(
                    new StringType(message: '模糊匹配关键词格式不正确'),
                    incorrectInputMessage: '模糊匹配关键词必须是数组',
                ),
            ],

            'full_match' => [
                new Each(
                    new StringType(message: '精准匹配关键词格式不正确'),
                    incorrectInputMessage: '精准匹配关键词必须是数组',
                ),
            ],

            // 关键词不能全为空
            new Callback(
                static function (mixed $value, Callback $rule, ValidationContext $context) {
                    $partial_match = $context->getDataSet()->getAttributeValue('partial_match');
                    $full_match = $context->getDataSet()->getAttributeValue('full_match');
                    if (empty($partial_match) && empty($full_match)) {
                        return (new Result)->addError('关键词不能全为空,请写模糊关键词或精准关键词');
                    }
                    return new Result;
                },
                skipOnError: true,
            ),


            'check_type' => [
                new Required(message: '生效用户不能为空'),
                new In(EnumCheckType::getValues(), message: '取值不合法'),
            ],
            'check_chat_type' => [
                new Required(message: '生效场景不能为空'),
                new In(EnumCheckType::getValues(), message: '取值不合法'),
            ],

            //规则内容
            'rules_list' => [//5个规则
                new Count(  min:1, max:5,
                    incorrectInputMessage: '规则必须是数组',
                    lessThanMinMessage: '规则数量必须为1到5个',
                    greaterThanMaxMessage: '规则数量必须为1到5个',
                    notExactlyMessage: '规则数量必须为1到5个'),
                new Each(
                    new Nested([
                        'toggle_interval' => [  //频率 1.天 2.周 3.月
                            new Required('规则频率不能为空'),
                            new In(EnumToggleInterval::getValues(), message: '取值不合法'),
                        ],
                        'toggle_num' => [
                            new Required('触发次数不能为空'),
                            new Integer( min:1, incorrectInputMessage:'触发次数必须是正整数',lessThanMinMessage: '触发次数不能小于1'),
                        ],
                        'tag_ids' => [
                            new Required('打标签不能为空'),
                            new Each(
                                [
                                    new StringType(message: '生效员工列表格式不正确',),
                                    new Length(min:32,max: 64,lessThanMinMessage: '规则标签id长度不能少于32位', greaterThanMaxMessage: '规则标签id长度不能超过64位'),
                                ],
                                incorrectInputMessage: '生效员工必须是数组',
                            ),
                        ],
                    ]),
                ),
            ],
            'switch' => [
                new In(EnumSwitch::getValues(), message: '任务开关不合法')
            ],

        ];
    }
}
