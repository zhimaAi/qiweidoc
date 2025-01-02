<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\KeywordsTagging\DTO;

use Common\DTO\CommonDTO;

use DateTime;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Type\StringType;
use Yiisoft\Validator\ValidationContext;

/**
 * @details 查询触发日志
 * @author ivan
 * @date 2024/12/24 10:08
 * Class QueryRuleTriggerLogDTO
 */
class QueryRuleTriggerLogDTO extends CommonDTO
{
    public function getRules(): array
    {
        return [
            'task_id'=>[
                new Integer(min:1,
                    incorrectInputMessage: '任务ID不合法',
                    notNumberMessage: '任务ID必须为整数',
                    lessThanMinMessage: '任务ID不能小于1',skipOnEmpty: true),
            ],
            'page'=>[
                new Integer(min:1,
                    incorrectInputMessage: '页不合法',
                    notNumberMessage: '页为整数',
                    lessThanMinMessage: '页不能小于1',skipOnEmpty: true),
            ],
            'size'=>[
                new Integer(min:1,
                    incorrectInputMessage: '每页数量不合法',
                    notNumberMessage:'每页数量为整数',
                    lessThanMinMessage: '每页数量不能小于1',skipOnEmpty: true),
            ],
            'customer_name'=>[
                new StringType(message: '客户名称不合法',skipOnEmpty: true)
            ],
            'start_time' => [
                new StringType(message: '开始时间不合法',skipOnEmpty: true)
            ],
            'end_time' => [
                new StringType(message: '结束时间不合法',skipOnEmpty: true)
            ],

            // 检测时间格式
            new Callback(
                static function (mixed $value, Callback $rule, ValidationContext $context) {
                    $start_time = $context->getDataSet()->getAttributeValue('start_time');
                    $end_time = $context->getDataSet()->getAttributeValue('end_time');
                    //有一个不为空
                    if (empty($start_time) ^ empty($end_time)) {
                        return (new Result)->addError('开始和结束时间必须成对传递！');
                    }

                    if (!empty($start_time) && !empty($end_time)){
                        $format = 'Y-m-d H:i:s';
                        $checkStartTime = DateTime::createFromFormat($format, $start_time);
                        $checkEndTime = DateTime::createFromFormat($format, $start_time);
                        if ($checkStartTime === false ) {
                            return (new Result)->addError('开始时间格式不合法，请传递Y-m-d H:i:s格式');
                        }
                        if ($checkEndTime === false ) {
                            return (new Result)->addError('结束时间格式不合法，请传递Y-m-d H:i:s格式');
                        }
                    }
                    return new Result;
                },
                skipOnError: true,
            ),


        ];
    }
}
