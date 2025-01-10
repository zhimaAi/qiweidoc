<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\KeywordsTagging\Consumer;

use Carbon\Carbon;
use Common\Broadcast;
use Common\Module;
use Common\Yii;
use Modules\KeywordsTagging\Enum\EnumCheckType;
use Modules\KeywordsTagging\Enum\EnumDelStatus;
use Modules\KeywordsTagging\Enum\EnumSwitch;
use Modules\KeywordsTagging\Enum\EnumToggleInterval;
use Modules\KeywordsTagging\Model\CheckTaskModel;
use Modules\KeywordsTagging\Model\KeywordsTriggerLogModel;
use Modules\KeywordsTagging\Model\MarkTagTaskModel;
use Modules\KeywordsTagging\Model\RuleTriggerLogModel;
use Modules\Main\Enum\EnumChatMessageRole;
use Modules\Main\Enum\EnumMessageType;
use Modules\Main\Model\ChatMessageModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\CustomersModel;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

/**
 * @author rand
 * @ClassName MsgCheckKeywordsConsumer
 * @date 2024/12/611:26
 * @description
 */
class MsgCheckKeywordsConsumer
{
    const TriggerNumCacheKey='TaskTriggerNum';
    const TriggerNumCacheTime=60*10; //换成10分钟 触发次数
    const TagIdTriggerCacheKey='TagIdTrigger';
    const TagIdTriggerCacheTime=60*60; //缓存标签检测1小时
    private readonly CorpModel $corp;

    public function __construct($corp)
    {
        $this->corp = $corp;
    }

    public function handle(): void
    {
        //获取是否存关键词规则
        $TaskList = MarkTagTaskModel::query()
            ->where([
                "corp_id" => $this->corp->get("id"),
                "switch" => EnumSwitch::SwitchOn->value,
                "del_status" => EnumDelStatus::Normal->value,
            ])->getAll();

        //没有 更新消息到当前消息时间
        if ($TaskList->isEmpty()) {
            return;
        }
        //更新查询时间
        $task_created_at_list=[];
        foreach ($TaskList as $taskInfo){
           $created_at= $taskInfo->get("created_at");
           if (!empty($created_at)){
               $task_created_at_list[]=$created_at;
           }
        }
        //获取 启动任务中最小的时间
        $baseMsgTime = min($task_created_at_list);
        //查询消息列表
        $lastCheck = CheckTaskModel::query()->where(["corp_id" => $this->corp->get("id")])->getOne();
        if (!empty($lastCheck)) {
            $baseMsgTime=max($lastCheck->get("last_msg_time"),$baseMsgTime);
        }
        $moduleInfo = Module::getLocalModuleConfig(Module::getCurrentModuleName());
        $moduleStartedAt = $moduleInfo['started_at'] ?? Carbon::today()->format('Y-m-d H:i:s.v');
        //获取模块启动时间 中大的时间
        $baseMsgTime=  max($moduleStartedAt,$baseMsgTime);
        //检测时间
        $last_msg_time = 0;
        $last_msg_id='';
        //遍历消息列表
        $page = 1;
        $limit = 500;
        $msgQuery = ChatMessageModel::query()
            ->where(["corp_id" => $this->corp->get("id")])
            ->andWhere([">", "msg_time", $baseMsgTime])
            ->orderBy("msg_time");
        while (true) {
            $offset = ($page - 1) * $limit;
            $msgList = $msgQuery->offset($offset)->limit($limit)->getAll();
            if ($msgList->isEmpty()) {
                break;
            }
            //遍历验证消息
            foreach ($msgList as $msgInfo) {
                $last_msg_time = $msgInfo->get("msg_time");
                $last_msg_id=$msgInfo->get("msg_id");
                foreach ($TaskList as $rule) {
                    try {
                        $this->checkMsg($msgInfo, $rule);
                    } catch (\Exception $exception) {
                        ddump($exception->getTraceAsString());
                    }
                }
            }
            //每次查询跑完更新一下时间
            if (!empty($last_msg_time)) {
                CheckTaskModel::updateOrCreate([
                    "corp_id" => $this->corp->get("id"),
                ], [
                    "corp_id" => $this->corp->get("id"),
                    "last_msg_time" => $last_msg_time,
                    "last_msg_id" => $last_msg_id,
                ]);
            }
            $page++;
        }
        //整个项目跑完再更新一下时间
        if (!empty($last_msg_time)) {
            //跑完了，更新一下消息时间
            CheckTaskModel::updateOrCreate([
                "corp_id" => $this->corp->get("id"),
            ], [
                "corp_id" => $this->corp->get("id"),
                "last_msg_time" => $last_msg_time,
                "last_msg_id" => $last_msg_id,
            ]);
        }
    }


    /**
     * 检测关键词是否触发
     * @param ChatMessageModel $msg
     * @param MarkTagTaskModel $task
     * @return bool
     * @author ivan
     * @date 2024/12/26 16:14
     */
    public function checkMsg(ChatMessageModel $msg,  MarkTagTaskModel $task): bool
    {
        //如果会话检测类型 和 消息类型不一致的 跳过
        if ( $task->get("check_chat_type")->value != $msg->get("conversation_type")->value) {
            //ddump(["会话与检测类型不一致",'check_chat_type'=> $task->get("check_chat_type")->value,'conversation_type'=> $msg->get("conversation_type")->value]);
            return false;
        }

        //如果消息发送人是指定角色   不是全部 且 发送方 与 规则检测不相同 跳过
        if ($task->get("check_type")->value != EnumCheckType::CustomAndStaff->value  && $task->get("check_type")->value != $msg->get("from_role")->value) {
            //ddump(["会话和监听类型不一致",'check_type'=> $task->get("check_type")->value,'from_role'=> $msg->get("from_role")->value]);
            return false;
        }

        //判断msg_type
         if ($msg->get("msg_type") != EnumMessageType::Text->value) {
             //不是文本的 不触发 跳过
             //ddump(["消息格式不是文本"=>$msg->get("msg_type") ]);
             return false;
         }

         $corp_id=$this->corp->get("id");
         $task_id=$task->get("id") ?? 0;
        //是否匹配关键词
        $msg_id=$msg->get("msg_id") ?? "";
        $msg_time = $msg->get("msg_time") ?? 0;
        $msg_content = $msg->get("msg_content") ?? "";
        $partial_match= $task->get("partial_match") ??[];
        $full_match= $task->get("full_match") ??[];
        $rules_list= $task->get("rules_list") ??[];


        $keyword='';
        if (!empty($full_match)){
            if (in_array($msg_content,$full_match)){
                $keyword=$msg_content;
            }
        }
        //检测模糊的
        if (empty($keyword) && !empty($partial_match)){
            $regex = '/(' . implode('|', $partial_match) . ')/i';
            $matches=[];
            $keyword=preg_match($regex,$msg_content,$matches);
            if (!empty($matches[1])){
                $keyword=$matches[1];
            }
        }
        //判断是否触发
        if (empty($keyword)){
            //未触发 跳过
            //ddump(["无触发关键词"=>$msg_content,'full_match'=>$full_match,'partial_match'=>$partial_match]);
            return false;
        }

        //判断是否属于任务监听的 员工
        $staff_userid_list= $task->get("staff_userid_list") ??[];
        $staff_customer=[];
        //检测员工是否一致
        if ($msg->get("from_role") == EnumChatMessageRole::Staff){
            //员工信息
            $msg_staff=$msg->get("from");
            if (!in_array($msg_staff,$staff_userid_list)){
                //不在范围内 跳过
                //ddump("员工发送消息，不在监听员工范围内 跳过{$msg_staff}");
                return false;
            }
            $msg_to_list=$msg->get("to_list"); //中筛选出 对应员工的客户 查客户表
            $staff_customer= $this->getStaffCustomer($corp_id,[$msg_staff],$msg_to_list);

        }else if ($msg->get("from_role") == EnumChatMessageRole::Customer){
            //客户信息  查看是否在消息范围内
            $msg_customer=$msg->get("from");
            //获取客户对应的员工
            if ($msg->get("to_role") == EnumChatMessageRole::Staff){
                $msg_staff=$msg->get("to_list")[0]??'';
                if (!in_array($msg_staff,$staff_userid_list)){
                    //不在范围内 跳过
                    //ddump("客户发送消息给员工，员工不在监听员工范围内 跳过！{$msg_staff}");
                    return false;
                }
                $staff_customer= $this->getStaffCustomer($corp_id,[$msg_staff],[$msg_customer]);
            }else{
                //群聊 查看群聊中是否有员工的客户  $staff_userid_list  //查客户表
                $msg_to_list=$msg->get("to_list"); //中筛选出 对应员工的客户
                //先判断 员工是否在通知范围内
                $msg_staff=array_intersect($staff_userid_list,$msg_to_list);
                if (empty($msg_staff)){
                    //不在范围内 跳过
                    //ddump("客户发送消息给群，员工不在监听员工范围内 跳过！");
                    return false;
                }
                //查看范围内的员工是否有对应的客户  // 查客户表
                $staff_customer= $this->getStaffCustomer($corp_id,$msg_staff,[$msg_customer]);
            }
        }
        if (empty($staff_customer)){
            //ddump(["无对应客户 跳过."=>$msg->get("from_role"),'staff_userid_list'=>$staff_userid_list,'from'=>$msg->get("from"),"to_list"=>$msg->get("to_list")]);
            //没有客户 跳过
            return false;
        }
        //写触发日志 //检测是否已经记录这个msg的记录 防止重复记录
        foreach ($staff_customer as $key=> $customer){
            $staff_userid=$customer["staff_userid"]??'';
            $external_userid=$customer["external_userid"]??'';
            if (empty($staff_userid) && empty($external_userid)){
               // ddump("员工的客户信息不完整 跳过..");
                continue;
            }

            //判断这个消息是否处理过
            $triggerLog=[
                "corp_id" => $corp_id,
                "staff_userid" => $staff_userid, // 员工user_id
                "external_userid" => $external_userid, // 客户external_userid
                "task_id" => $task_id, // 任务id
                "keyword" => $keyword, // 匹配关键词
                "msg_id" => $msg_id, // 企业微信消息唯一id
                "msg_time" => $msg_time // 消息发出时间
            ];
           $old= KeywordsTriggerLogModel::query()->where($triggerLog)->getOne();
           if (!empty($old)){
               //处理过 跳过
               ddump("处理过的消息 跳过..");
               continue;
           }
            //添加当天的触发次数缓存
            $this->AddTaskTrigger($task_id,$staff_userid,$external_userid,$msg_time);
            //添加触发 数据库添加日志
            KeywordsTriggerLogModel::create($triggerLog);
            //判断规则 是否触发打标签
            foreach ($rules_list as $rIndex=> $rule){
                $toggle_interval= $rule["toggle_interval"]??EnumToggleInterval::Day->value;
                $toggle_num= $rule["toggle_num"]??0;
                //判断范围内的触发次数
                $tag_ids= $rule["tag_ids"]??'';
                //判断时间范围的触发次数
                $curNum=$this->GetTaskTrigger($task_id,$staff_userid,$external_userid,$msg_time,$toggle_interval);
                //次数优先
                if ($curNum != $toggle_num) {
                    //会不会因为数据库添加 导致次数跳过去
                    continue;
                }
                //检测触发打标签
                foreach ($tag_ids as $tag_id){
                    //记录触发日志
                    $triggerLog=[
                        "corp_id" => $corp_id, // 所属企业微信id
                        "staff_userid" => $staff_userid, // 员工user_id
                        "external_userid" =>$external_userid, // 客户external_userid
                        "task_id" => $task_id, // 任务id
                        "keyword" => $keyword, // 匹配关键词
                        "rule_info" =>$rule, // 判断的规则信息
                        "trigger_num" =>$curNum, // 当前规则范围关键词触发次数
                        "msg_id" => $msg_id, // 企业微信消息唯一id
                        "msg_time" => $msg_time, // 消息发出时间
                        "tag_ids" => $tag_id // 打标签id
                    ];
                    //发送打标签事件
                    RuleTriggerLogModel::create($triggerLog);
                    //批次号 不是唯一的
                    $batch_number='keywords_'.$task_id.'_'.$rIndex.'_'.$toggle_interval.'_'.$toggle_num.'_'.$curNum;
                    //发送打标签事件
                    $MarkTagTask=[
                        'corp_id'=>$corp_id,
                        'add_tag'=>[$tag_id],
                        'userid'=>$staff_userid,
                        'external_userid'=>$external_userid,
                        'batch_number'=>$batch_number,
                        'source'=>'keywords_trigger',
                    ];
                    Broadcast::event('mark_tag')->send(json_encode($MarkTagTask));
                }
            }
        }
        return true;
    }


    /**
     * 获取任务触发次数
     * @param $task_id
     * @param $staff_userid
     * @param $external_userid
     * @param $msg_time
     * @param $toggle_interval
     * @return mixed
     * @throws \DateMalformedStringException
     * @author ivan
     * @date 2024/12/28 10:12
     */
    public function GetTaskTrigger($task_id,$staff_userid,$external_userid,$msg_time,$toggle_interval):int{
        $day=date('Ymd',strtotime($msg_time));
        $key=self::TriggerNumCacheKey.$task_id.md5("{$day}_{$toggle_interval}_{$task_id}_{$staff_userid}_{$external_userid}");
        $where=[
            'task_id' => $task_id,
            'staff_userid' => $staff_userid,
            'external_userid' => $external_userid,
        ];
        $timeFrameBetween= EnumToggleInterval::from($toggle_interval)->getTimeFrameBetween($msg_time);
        $start_time=$timeFrameBetween['start']??date('Y-m-d H:i:s');
        $end_time=$timeFrameBetween['end']??date('Y-m-d H:i:s');
        $addWhere=['BETWEEN', 'msg_time', $start_time, $end_time];
        $triggerNum = Yii::cache()->getOrSet(
            $key,
            function () use ($where,$addWhere) {
                return KeywordsTriggerLogModel::query()
                    ->where($where)
                    ->andWhere($addWhere)
                    ->count();
            },
            self::TriggerNumCacheTime  //缓存次数
        );
        return $triggerNum;
    }

    /**
     * 添加任务触发次数
     * @param $task_id
     * @param $staff_userid
     * @param $external_userid
     * @param $msg_time
     * @return void
     * @throws InvalidArgumentException
     * @author ivan
     * @date 2024/12/28 10:12
     */
    public function AddTaskTrigger($task_id,$staff_userid,$external_userid,$msg_time): void
    {
        $day=date('Ymd',strtotime($msg_time));

        foreach (EnumToggleInterval::getValues() as $toggle_interval){
            $key=self::TriggerNumCacheKey.$task_id.md5("{$day}_{$toggle_interval}_{$task_id}_{$staff_userid}_{$external_userid}");
            $triggerNum= $this->GetTaskTrigger($task_id,$staff_userid,$external_userid,$msg_time,$toggle_interval);
            //触发次数+1
            $triggerNum=intval($triggerNum) + 1;
            Yii::cache()->psr()->set($key, $triggerNum, self::TriggerNumCacheTime);
        }
    }

    /**
     * 删除规则标签是否在本任务中标识
     * @param $task_id
     * @param $staff_userid
     * @param $external_userid
     * @param $tag_id
     * @return void
     * @throws InvalidArgumentException
     * @author ivan
     * @date 2024/12/28 20:43
     */
    public function delRuleTagIdTrigger($task_id,$staff_userid,$external_userid,$tag_id){
        $key=self::TagIdTriggerCacheKey.md5("{$external_userid}_{$tag_id}_{$task_id}_{$staff_userid}");
        Yii::cache()->psr()->delete($key);
    }

    /**
     * 获取规则标签是否在本任务中触发
     * @deprecated 标签为主的检测
     * @param $task_id
     * @param $staff_userid
     * @param $external_userid
     * @param $tag_id
     * @return int|string
     * @author ivan
     * @date 2024/12/28 11:59
     */
    public function checkRuleTagIdTrigger($task_id,$staff_userid,$external_userid,$tag_id){
        //标签优先
        //获取规则内触发次数 暂时不考虑 活跃标签这种的重复打标签的
        $where=[
            "task_id" => $task_id, // 任务id
            "staff_userid" => $staff_userid, // 员工user_id
            "external_userid" => $external_userid, // 客户external_userid
            "tag_ids" => $tag_id,
        ];
        $key=self::TagIdTriggerCacheKey.md5("{$external_userid}_{$tag_id}_{$task_id}_{$staff_userid}");
        $triggerId = Yii::cache()->getOrSet(
            $key,
            function () use ($where) {
               return   RuleTriggerLogModel::query()
                    ->where($where)
                    ->getOne()?->get("id");
            },
            self::TagIdTriggerCacheTime  //缓存次数
        );
        return  $triggerId;
    }

    /**
     * 返回员工对应的客户
     * @param $corp_id
     * @param array $staff_userid
     * @param array $external_userid
     * @return array
     * @throws Throwable
     * @author ivan
     * @date 2024/12/28 09:38
     */
    public function getStaffCustomer($corp_id, array $staff_userid=[], array $external_userid=[]): array
    {
        if (empty($staff_userid) && empty($external_userid)){
            return [];
        }
        //ddump(['查客户消息','corp_id'=>$corp_id,'staff_userid'=>$staff_userid,'external_userid'=>$external_userid]);
        //获取员工对应的客户
        return CustomersModel::query()->where(['corp_id'=>$corp_id])
            ->andWhere(['in','staff_userid',$staff_userid])
            ->andWhere(['in','external_userid',$external_userid])->getAll()->toArray()?:[];
    }
}
