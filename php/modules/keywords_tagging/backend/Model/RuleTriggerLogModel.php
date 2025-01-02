<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\KeywordsTagging\Model;


use Common\DB\BaseModel;
use Yiisoft\Arrays\ArrayHelper;

/**
 * @description  聊天关键词规则触发打标签日志表
 * @author ivan
 */
class RuleTriggerLogModel extends BaseModel
{

    public function getTableName(): string
    {
        return "keywords_tagging.rule_trigger_log";
    }

    protected function casts(): array
    {
        return [
            "id" => "int", // 主键ID
            "created_at" => "string", // 创建时间
            "updated_at" => "string", // 更新时间
            "corp_id" => "string", // 所属企业微信id
            "staff_userid" => "string", // 员工user_id
            "external_userid" => "string", // 客户external_userid
            "task_id" => "int", // 任务id
            "keyword" => "string", // 匹配关键词
            "rule_info" => "array", // 判断的规则信息
            "trigger_num" => "int", // 当前规则范围关键词触发次数
            "msg_id" => "string", // 企业微信消息唯一id
            "msg_time" => "string", // 消息发出时间
            "tag_ids" => "string" // 打标签id
        ];
    }


    /**
     * @description  获取任务Id 统计数据通过任务id分组
     * @param $corp_id
     * @param $task_ids
     * @return array|array[]
     * @throws \Throwable
     * @throws \Yiisoft\Db\Exception\Exception
     * @throws \Yiisoft\Db\Exception\InvalidConfigException
     * @author ivan
     * @date 2024/12/23 18:21
     */
    public static function getTotalStatistics($corp_id=0,$task_ids=[]):array {

        if (empty($corp_id) || empty($task_ids)){
            return [];
        }

        if (!is_array($task_ids)){
            $task_ids=[$task_ids];
        }

        $list= self::query()->select('count(*) as num, task_id')->where([
            'corp_id'=>$corp_id,
        ])->andWhere(['in','task_id',$task_ids])->groupBy('task_id')->all();
       if (!empty($list)){
           $list= ArrayHelper::index($list,'task_id');
       }else{
           $list=[];
       }
       return $list;
    }

    /**
     * 获取今日统计
     * @param $corp_id
     * @param $task_ids
     * @return array[]
     * @throws \Throwable
     * @throws \Yiisoft\Db\Exception\Exception
     * @throws \Yiisoft\Db\Exception\InvalidConfigException
     * @author ivan
     * @date 2024/12/23 18:34
     */
    public static function getTodayStatistics($corp_id=0,$task_ids=[]): array
    {
        $startTime=date('Y-m-d 00:00:00');
        $endTime=date('Y-m-d 23:59:59');
        $list= self::getTotalStatisticsByTime($corp_id,$task_ids,$startTime,$endTime);
        return $list;
    }

    /**
     * 获取昨日统计
     * @param $corp_id
     * @param $task_ids
     * @return array[]
     * @throws \Throwable
     * @throws \Yiisoft\Db\Exception\Exception
     * @throws \Yiisoft\Db\Exception\InvalidConfigException
     * @author ivan
     * @date 2024/12/23 18:34
     */
    public static function getYesterdayStatistics($corp_id=0,$task_ids=[]): array
    {
        $startTime=date('Y-m-d 00:00:00',strtotime('-1 day'));
        $endTime=date('Y-m-d 23:59:59',strtotime('-1 day'));
        $list= self::getTotalStatisticsByTime($corp_id,$task_ids,$startTime,$endTime);
        return $list;
    }

    /**
     * 获取时间范围的统计
     * @param $corp_id
     * @param $task_ids
     * @param $startTime
     * @param $endTime
     * @return array|array[]
     * @throws \Throwable
     * @throws \Yiisoft\Db\Exception\Exception
     * @throws \Yiisoft\Db\Exception\InvalidConfigException
     * @author ivan
     * @date 2024/12/23 18:28
     */
    public static function getTotalStatisticsByTime($corp_id=0,$task_ids=[],$startTime='',$endTime=''): array
    {

        if (empty($startTime) || empty($endTime)){
            $startTime=date('Y-m-d 00:00:00');
            $endTime=date('Y-m-d 23:59:59');
        }
        if (empty($corp_id) || empty($task_ids)){
            return [];
        }

        if (!is_array($task_ids)){
            $task_ids=[$task_ids];
        }

        $list= self::query()->select('count(*) as num,task_id')->where([
            'corp_id'=>$corp_id,
        ])->andWhere(['in','task_id',$task_ids])
            ->andWhere(['BETWEEN','msg_time',$startTime,$endTime])
            ->groupBy('task_id')->all();
        if (!empty($list)){
            $list= ArrayHelper::index($list,'task_id');
        }else{
            $list=[];
        }
        return $list;
    }

    /**
     * 获取企业统计信息
     * @param $corp_id
     * @return array
     * @throws \Throwable
     * @throws \Yiisoft\Db\Exception\Exception
     * @throws \Yiisoft\Db\Exception\InvalidConfigException
     * @author ivan
     * @date 2024/12/23 18:55
     */
    public static function getCorpStatistics($corp_id=0,$search=[]): array
    {
        $task_id=intval($search['task_id']??0);

        $baseWhere=[
            'corp_id'=>$corp_id,
        ];
        //支持只查一个
        if (!empty($task_id)){
            $baseWhere['task_id']=$task_id;
        }
        //获取信息
        $totalNum= self::query()->where($baseWhere)->count();

        //获取今日
        $todayNum= self::query()->where($baseWhere)->andWhere(['BETWEEN','msg_time',date('Y-m-d 00:00:00'),date('Y-m-d 23:59:59')] )->count();

        //获取昨日
        $startTime=date('Y-m-d 00:00:00',strtotime('-1 day'));
        $endTime=date('Y-m-d 23:59:59',strtotime('-1 day'));
        $yesterdayNum= self::query()->where($baseWhere)->andWhere(['BETWEEN','msg_time',$startTime,$endTime ])->count();

        //获取统计
        return [
            'total_num'=>$totalNum,
            'today_num'=>$todayNum,
            'yesterday_num'=>$yesterdayNum,
        ];
    }
}
