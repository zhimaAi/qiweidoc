<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\KeywordsTagging\Service;

use Common\DB\ModelCollection;
use Common\Yii;
use LogicException;
use Modules\KeywordsTagging\DTO\MarkTagTaskDTO;
use Modules\KeywordsTagging\DTO\QueryRuleTriggerLogDTO;
use Modules\KeywordsTagging\DTO\UpMarkTagTaskSwitchDTO;
use Modules\KeywordsTagging\Enum\EnumDelStatus;
use Modules\KeywordsTagging\Enum\EnumSwitch;
use Modules\KeywordsTagging\Model\MarkTagTaskModel;
use Modules\KeywordsTagging\Model\RuleTriggerLogModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\CustomersModel;
use Modules\Main\Model\StaffModel;
use Modules\Main\Model\UserModel;
use Throwable;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;

/**
 * @details 关键词打标签 服务
 * @author ivan
 * @date 2024/12/23 14:45
 * Class TaskService
 */
class TaskService
{
    const TagIdLengthLimit = 31; // 标签长度32 所以要大于31

    /**
     * 获取关键词打标签任务列表
     * @param CorpModel $corp
     * @param $data
     * @return array
     * @throws Throwable
     * @author ivan
     * @date 2024/12/23 14:45
     */
    public static function list(CorpModel $corp, $data): array
    {
        $query = MarkTagTaskModel::query()->where(['corp_id' => $corp->get('id'), "del_status" => EnumDelStatus::Normal->value]);

        $res = $query->orderBy(['id' => SORT_DESC])->paginate($data['page'] ?? 1, $data['size'] ?? 10);
        //格式化数据
        if (!$res['items']->isEmpty()){
            //格式化数据
            $res['items']=self::FormatTask($res['items'], $corp);
        }


        return $res;
    }

    /**
     * 删除关键词打标签任务
     * @param CorpModel $corp
     * @param $id
     * @return bool
     * @throws Throwable
     * @author ivan
     * @date 2024/12/23 14:44
     */
    public static function delete(CorpModel $corp,UpMarkTagTaskSwitchDTO $upDto): void
    {
        //删除 停止
        $id=$upDto->get("id");
       $task= MarkTagTaskModel::query()->where(["corp_id" => $corp->get("id"), "id" =>$id ?? 0])->getOne();
       if (empty($task)){
           throw new LogicException("任务不存在");
       }
        $task->update(["del_status" => EnumDelStatus::DELETED->value]);
    }

    /**
     * 保存关键词打标签任务
     * @param CorpModel $corp
     * @param UserModel $currentUser
     * @param MarkTagTaskDTO $taskDTO
     * @return bool
     * @throws Throwable
     * @author ivan
     * @date 2024/12/23 14:44
     */
    public static function save(CorpModel $corp, MarkTagTaskDTO $taskDTO): bool
    {

        $data=$taskDTO->toArray();

        $corp_id=$corp->get("id");
        if (empty($corp_id)){
            throw new LogicException("企业不存在");
        }

        //检测名称
        $name=$data["name"];
        $id=$data["id"]??0;
        $checkNameTask=MarkTagTaskModel::query()->where(["corp_id" => $corp->get("id"), "name" => $name]);
        if (!empty($id)){
            $checkNameTask->andWhere(["!=", "id", $id]);
        }
        $checkTask= $checkNameTask->getOne();
        if (!empty($checkTask)){
            throw new LogicException("规则名称已存在，请重新输入");
        }

        $hisTask=[];
        //检测是否已删除
        if (!empty($data["id"])){
            //检测是否已删除
            $hisTask=MarkTagTaskModel::query()->where(["corp_id" => $corp->get("id"), "id" => $data["id"]])->getOne();
            if (empty($hisTask)){
                throw new LogicException("修改的任务不存在");
            }
            if ($hisTask->get("del_status") === EnumDelStatus::DELETED){
                throw new LogicException("任务已删除无法修改");
            }
        }else{
            unset($data["id"]);
        }


        //修改配置
        if (!empty($hisTask)) {
            $hisTask->update($data);
            return true;
        }
        //保存配置
        MarkTagTaskModel::create(array_merge([
            "corp_id" => $corp->get('id'),
        ], $data));

        return true;
    }

    /**
     * 获取任务详情
     * @param CorpModel $corp
     * @param $data
     * @return array
     * @throws Throwable
     * @author ivan
     * @date 2024/12/23 15:19
     */
    public static function getTaskInfo(CorpModel $corp,UpMarkTagTaskSwitchDTO $data): array
    {
        $id=$data->get("id");
        if (empty($id)){
            throw new LogicException("规则id不能为空");
        }
        $one=MarkTagTaskModel::query()->where(['corp_id' => $corp->get('id'), "id" => $id])->getOne();
        if (empty($one)){
            throw new LogicException("规则不存在，请刷新重试！");
        }
        if ($one->get("del_status") === EnumDelStatus::DELETED){
            throw new LogicException("规则任务已删除，无法修改！请刷新重试");
        }
        $showInfo=self::FormatTask([$one], $corp);
        $one=$showInfo[0]??$one;
        return $one->toArray();
    }

    /**
     * 获取统计数据
     * @param CorpModel $corp
     * @return array
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     * @author ivan
     * @date 2024/12/23 19:06
     */
    public static function getStatistics(CorpModel $corp,$search = []): array
    {
        $corp_id=$corp->get("id");
        return RuleTriggerLogModel::getCorpStatistics($corp_id,$search);
    }

    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 变更规则启用状态
     * User: rand
     * Date: 2024/12/6 15:11
     * @return void
     * @throws Throwable
     */
    public static function changeSwitch(CorpModel $corp, UpMarkTagTaskSwitchDTO $data): void
    {

        $id=$data->get("id");
        if (empty($id)){
            throw new LogicException("规则id不能为空");
        }
        $one=MarkTagTaskModel::query()->where(['corp_id' => $corp->get('id'), "id" => $id])->getOne();
        if (empty($one)){
            throw new LogicException("规则不存在，请刷新重试！");
        }
        if ($one->get("del_status") == EnumDelStatus::DELETED){
            throw new LogicException("规则任务已删除，无法修改！请刷新重试");
        }
        $switch=$data->get("switch")??EnumSwitch::SwitchOff->value;

        $one->update(["switch" => $switch]);
    }



    /**
     * 获取标签名称
     * @param array $tag_id_arr
     * @param CorpModel $corp
     * @param $quick_cache
     * @return array
     */
    public static function  TranslateTagIdToName(array $tag_id_arr, CorpModel $corp,$quick_cache = false): array
    {
        if(empty($tag_id_arr)){
            return [];
        }

        try{
            $map = [];
            $tag_id_arr = array_filter($tag_id_arr, function($item){return $item;});
            $tag_id_arr = array_filter($tag_id_arr, function($item){return strlen($item)>self::TagIdLengthLimit;});
            $targetIdArr = array_values(array_unique($tag_id_arr));
            $corp_id= $corp->get('id');
            if(empty($targetIdArr) || empty($corp_id)){
                return [];
            }

            if( $quick_cache){
                $md5_key=md5(implode(',',$targetIdArr));
                $CorpTagKey='corp:tag:quick:cache:'.$corp_id.':'.$md5_key;
                $tag_list = Yii::cache()->getOrSet(
                    $CorpTagKey,
                    function () use ($corp,$targetIdArr) {
                        $params = ['tag_id' => $targetIdArr];
                        return $corp->postWechatApi("cgi-bin/externalcontact/get_corp_tag_list",$params,'json');
                    },
                    30  // 缓存30秒 //应对使用同一个标签的 打标签不需要每次都查询 30秒查1次就行
                );
            }else{
                $params = ['tag_id' => $targetIdArr];
                $tag_list = $corp->postWechatApi("cgi-bin/externalcontact/get_corp_tag_list",$params,'json');
            }
            if ($tag_list['errcode'] != 0) {
                throw new \Exception($tag_list['errmsg'], $tag_list['errcode']);
            }
           // ddump(['微信返回数据'=>$tag_list]);
            foreach ($tag_list['tag_group'] as $key => $value) {
                if ($value['tag']) {
                    foreach ($value['tag'] as $k => $v) {
                        if(!empty($v['deleted'])){//过滤已删除标签
                            continue;
                        }
                        $map[] =array_merge($v,[
                            'group_id' => $value['group_id'] ?? '',
                            'group_name' => $value['group_name'] ?? '',
                        ]);
                    }
                }
            }
            return $map;

        }catch (\Throwable $e) {
            //
            ddump(['有问题'=>$e->getLine(),'msg'=>$e->getMessage()]);
            return [];
        }
    }

    /**
     * 格式化任务列表
     * @param array|ModelCollection $taskList
     * @param CorpModel $corp
     * @return array|ModelCollection
     * @throws Throwable
     * @throws Exception
     * @throws InvalidConfigException
     * @author ivan
     * @date 2024/12/23 18:04
     */
    public static function FormatTask(array|ModelCollection $taskList,  CorpModel $corp): array|ModelCollection
    {
        if (empty($taskList)){
            return [];
        }
        $all_tag_id=[];
        $all_task_id=[];
        $all_staff_userid_list=[];
        //统计标签 和 任务Id
        foreach ($taskList as $k => $task) {
            $all_task_id[]=$task->get("id")??0;
            $ruleList = $task->get("rules_list")?:[];
            $staff_userid_list = $task->get("staff_userid_list")?:[];
            //获取标签
            foreach ($ruleList as $k1 => $ruleItem) {
                $all_tag_id = array_merge($all_tag_id, $ruleItem['tag_ids'] ?? []);
            }
            //获取员工信息
            foreach ($staff_userid_list as $k2 => $staff_userid){
                $all_staff_userid_list[$staff_userid]=$staff_userid;
            }
        }
        //获取统计信息
        //总数据
        $corp_id=$corp->get('id');
        $totalCount=RuleTriggerLogModel::getTotalStatistics($corp_id,$all_task_id);
        //当天
        $todayCount=RuleTriggerLogModel::getTodayStatistics($corp_id,$all_task_id);
        //昨天
        $yesterdayCount=RuleTriggerLogModel::getYesterdayStatistics($corp_id,$all_task_id);

        //获取标签信息
        $tag_info = self::TranslateTagIdToName($all_tag_id, $corp, true);
        if (!empty($tag_info)) {
            $tag_info = ArrayHelper::index($tag_info, 'id');
        }

        //缓存标签
        $all_tag_info = $tag_info;
        //查找员工信息
        $all_staff_info = StaffModel::query()->select(['userid', 'name'])->where(['corp_id' => $corp_id])->andWhere(['in', 'userid', array_values($all_staff_userid_list) ])->getAll()->toArray();
        if (!empty($all_staff_info)){
            $all_staff_info = ArrayHelper::index($all_staff_info, 'userid');
        }

        //处理显示标签
        foreach ($taskList as $k => &$task) {
            $ruleList = $task->get("rules_list");
            $taskAllTag = [];
            $task_id=$task->get("id")??0;
            //添加统计信息
            $task->append("total_count", $totalCount[$task_id]['num'] ?? 0);
            $task->append("today_count", $todayCount[$task_id]['num'] ?? 0);
            $task->append("yesterday_count", $yesterdayCount[$task_id]['num'] ?? 0);
            //添加标签信息
            foreach ($ruleList as $k1 => &$ruleItem) {
                //处理规则中每条规则的标签显示
                $curTagInfo = [];
                foreach ($ruleItem['tag_ids'] ?? [] as $tag_id) {
                    if (!empty($all_tag_info[$tag_id])) {
                        $curTagInfo[$tag_id] = $all_tag_info[$tag_id];
                    }
                }
                $ruleItem["tag"]= array_values($curTagInfo);
                //统计到总数据中
                $taskAllTag = array_merge($taskAllTag, $curTagInfo);
            }
            unset($ruleItem);
            $task->append("rules_list", $ruleList);
            //处理当前任务中所有标签显示
            $task->append("all_tag_info", array_values($taskAllTag));
            //显示员工信息
            $staff_userid_list = $task->get("staff_userid_list") ?? [];
            $staff_info_list=[];
            foreach ($staff_userid_list as $k2 => $staff_userid){
                if (!empty($all_staff_info[$staff_userid])){
                    $staff_info_list[]=$all_staff_info[$staff_userid];
                }
            }
            $task->append("staff_info_list", $staff_info_list);
        }
        unset($task);
        return $taskList;
    }


    /**
     * 获取规则触发日志列表
     * @param CorpModel $corp
     * @param QueryRuleTriggerLogDTO $search
     * @return array
     * @throws Throwable
     * @author ivan
     * @date 2024/12/24 10:51
     */
    public static function getRuleTriggerLogList(CorpModel $corp, QueryRuleTriggerLogDTO $search)
    {
        $page=$search->get("page")??1;
        $size=$search->get("size")??20;

        $customer_name=$search->get("customer_name")??'';
        $start_time=$search->get("start_time")??'';
        $end_time=$search->get("end_time")??'';
        $task_id=$search->get("task_id")??'';

        $customersModel = new CustomersModel();
        $joinTable=$customersModel->getTableName().' as cus';
        $queryWhere = RuleTriggerLogModel::query('log')
            ->select(['log.*', 'cus.external_name', 'cus.avatar','cus.corp_name'])
            ->leftJoin($joinTable, 'log.external_userid = cus.external_userid and log.staff_userid=cus.staff_userid')
            ->where(['log.corp_id' => $corp->get('id')]);
        if (!empty($task_id)){
            $queryWhere->andWhere(['log.task_id'=> $task_id]);
        }
        if (!empty($customer_name)){
            $queryWhere->andWhere(['like', 'cus.external_name', $customer_name]);
        }

        if (!empty($start_time) && !empty($end_time)){
            $queryWhere->andWhere(['between', 'log.msg_time', $start_time, $end_time]);
        }

       $res= $queryWhere ->orderBy(['log.msg_time' => SORT_DESC])->paginate($page, $size);
        //格式化数据
        if (!$res['items']->isEmpty()){
            //格式化数据
            $res['items']=self::FormatRuleTriggerLog($res['items'], $corp);
        }

        return $res;
    }

    /**
     * 格式化规则触发日志
     * @param $logList
     * @param CorpModel $corp
     * @return mixed
     * @throws Throwable
     * @author ivan
     * @date 2024/12/24 10:50
     */
    public static function FormatRuleTriggerLog($logList, CorpModel $corp): mixed
    {
        if (empty($logList)){
            return [];
        }
        $all_tag_id=[];
        $all_staff_userid_list=[];
        //统计标签 和 任务Id
        foreach ($logList as $k => $log) {
            $all_tag_id[]=$log->get("tag_ids")??0;
            $staff_userid=$log->get("staff_userid")??0;
            $all_staff_userid_list[$staff_userid]=$staff_userid;
        }
        if (empty($all_tag_id)){
            return $logList;
        }
        //获取标签信息
        $tag_info = self::TranslateTagIdToName($all_tag_id, $corp, true);
        if (!empty($tag_info)) {
            $tag_info = ArrayHelper::index($tag_info, 'id');
        }
        //缓存标签
        $all_tag_info = $tag_info;

        //查找员工信息
        $all_staff_info = StaffModel::query()->select(['userid', 'name'])->where(['corp_id' => $corp->get('id')])->andWhere(['in', 'userid', array_values($all_staff_userid_list) ])->getAll()->toArray();
        if (!empty($all_staff_info)){
            $all_staff_info = ArrayHelper::index($all_staff_info, 'userid');
        }
        //处理显示标签
        foreach ($logList as $k => &$log) {
            $tag_id=$log->get("tag_ids")??0;
            $staff_userid=$log->get("staff_userid")??0;
            //添加标签信息
            $curTagInfo = [];
            if (!empty($all_tag_info[$tag_id])) {
                $curTagInfo = $all_tag_info[$tag_id];
            }
            $log->append("tag", $curTagInfo);
            $log->append("staff_name", $all_staff_info[$staff_userid]['name']??'');
        }
        unset($log);
        return $logList;
    }
}
