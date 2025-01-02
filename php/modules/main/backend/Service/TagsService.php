<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Service;

use Common\Yii;
use LogicException;
use Modules\Main\Enum\EnumLogHandleType;
use Modules\Main\Enum\EnumLogStatus;
use Modules\Main\Model\CorpCstTagLogModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\CustomersModel;
use Modules\Main\Model\CustomerTagModel;
use Modules\Main\Model\StaffTagModel;
use Modules\Main\Model\UserModel;
use Throwable;

class TagsService
{
    const TagIdLengthLimit = 31; // 标签长度32 所以要大于31
    /**
     * @param UserModel $currentUser
     * @return iterable
     * @throws Throwable
     */
    public static function staff(UserModel $currentUser): iterable
    {
        return StaffTagModel::query()->where(["corp_id" => $currentUser->get('corp_id')])->getAll();
    }

    /**
     * @param CorpModel $corp
     * Notes: 客户标签
     * User: rand
     * Date: 2024/12/10 16:04
     * @return array|mixed
     * @throws Throwable
     */
    public static function customer(CorpModel $corp): mixed
    {
        $tagListResp = $corp->postWechatApi("/cgi-bin/externalcontact/get_corp_tag_list");
        $tagGroups = $tagListResp['tag_group'] ?? [];

        // 排序函数，根据order和create_time进行排序
        $sortFunction = function ($a, $b) {
            if ($a['order'] === $b['order']) {
                return $a['create_time'] <=> $b['create_time'];
            }
            return $a['order'] <=> $b['order'];
        };

        // 对标签组进行排序
        usort($tagGroups, $sortFunction);

        // 对每个标签组中的标签进行排序
        foreach ($tagGroups as $key => $group) {
            if (!empty($group['tag'])) {
                usort($group['tag'], $sortFunction);
                $tagGroups[$key]['tag'] = $group['tag'];  // 更新排序后的标签列表
            }
        }

        return $tagGroups;
    }

    /**
     * 过滤非标签数据
     * @param array $tag_id_arr
     * @return array
     */
    public static function FilterNotTag(array $tag_id_arr): array
    {
        if (empty($tag_id_arr) || !is_array($tag_id_arr)){
            return [];
        }
        $tag_id_arr = array_filter($tag_id_arr, function($item){return $item;});
        $tag_id_arr = array_filter($tag_id_arr, function($item){return strlen($item)>self::TagIdLengthLimit;});
        return array_values(array_unique($tag_id_arr));
    }

    /**
     * 处理标签服务
     * @param CorpModel $corp
     * @param array $tag_task
     * @throws Throwable
     */
    public static function MarkTag(CorpModel $corp, array $tag_task=[]): void
    {
        $corp_id= $corp->get('id');
        //验证数据
        if(empty($corp_id)){
            throw new LogicException('无企业id');
        }
        if (empty($tag_task) ){
            throw new LogicException('无任务数据');
        }
        $addTagList = $tag_task['add_tag']??[];
        $delTagList = $tag_task['remove_tag']??[];
        $userid=$tag_task['userid']??'';
        $external_userid=$tag_task['external_userid']??'';

        $batch_number=$tag_task['batch_number']??'';
        $source=$tag_task['source']??'';
        //没有 员工id 或者 外部联系人id
        if (empty($userid) || empty($external_userid)){
            throw new LogicException('员工id或外部联系人id为空');
        }
        //过滤标签
        $addTagList=self::FilterNotTag($addTagList);
        $delTagList=self::FilterNotTag($delTagList);

        // 没有标签  不处理
        if (empty($addTagList) && empty($delTagList)){
            throw new LogicException('添加标签列表和删除标签列表同时为空');
        }

        //检测客户已有标签
        $customerModel = CustomersModel::query()
            ->where(['and',
                ["corp_id" => $corp_id],
                ["staff_userid" => $userid],
                ["external_userid" => $external_userid],
            ])
            ->getOne();
        //没有客户 不打标签
        if (empty($customerModel)){
            throw new LogicException('无客户数据');
        }

        //触发打标签
        $params = [
            'userid' => $userid,
            'external_userid' => $external_userid,
            'add_tag' => $addTagList,
            'remove_tag' => $delTagList,
        ];
        $resp = $corp->postWechatApi("/cgi-bin/externalcontact/mark_tag",$params, 'json');
        $status = EnumLogStatus::SUCCESS;
        $fail_reason='';
        $handle_type=EnumLogHandleType::ADD;

        if (!empty($resp['errcode']) ){
            $status=EnumLogStatus::FAIL;
            $fail_reason=$resp['errmsg']??'';
        }
        //启动事务
        $transaction = Yii::db()->beginTransaction();
        try {
            //更新日志
            if ($status==EnumLogStatus::SUCCESS){
                $newTagList=[];
                $customerModel = CustomersModel::query()
                    ->where(['and',
                        ["corp_id" => $corp_id],
                        ["staff_userid" => $userid],
                        ["external_userid" => $external_userid],
                    ])
                    ->getOne();
                $curTagList=$customerModel->get('staff_tag_id_list');
                // 过滤 客户表中存在的
                if (!empty($addTagList)){
                    //添加新加标签
                    $newTagList=array_merge($curTagList,$addTagList);
                    $newTagList=self::FilterNotTag($newTagList);
                }
                if (!empty($delTagList)){
                    //移除删除标签
                    $newTagList = array_values(array_diff($curTagList,$delTagList));
                    $newTagList=self::FilterNotTag($newTagList);
                }
                //成功更新标签
                $data = [
                    'staff_tag_id_list' => $newTagList,
                ];
                $customerModel->update($data);
            }

            //记录日志  //更新 标签二进制
            $baseLog=[
                "corp_id" =>$corp_id, // 企业ID
                "tag_id" => '', // 标签ID（单个标签）
                "external_userid" =>$external_userid, // 客户external_userid
                "userid" => $userid, // 员工user_id
                "source" => $source, // 来源 标识 最大64位
                "batch_number" => $batch_number, // 批次号 批量标识 最大64位
                "handle_time" => now(), // 处理时间
                "time_to_date" => date("Ymd"), // Ymd格式日期
                "handle_type" => $handle_type, // 处理类型 0：默认数据  1:删除标签，2:新增标签
                "status" => $status, // 状态
                "fail_reason" => $fail_reason, // 失败原因
            ];
            //记录 新增标签日志
            if (!empty($addTagList)){
                foreach($addTagList as $tag_id){
                    $addLogData=array_merge($baseLog,[
                        "tag_id"=>$tag_id,
                        "handle_type"=>EnumLogHandleType::ADD,
                    ]);
                    CorpCstTagLogModel::create($addLogData);//写库
                    //更新标签客户
                    if ($status==EnumLogStatus::SUCCESS){
                        CustomerTagModel::addCustomerTag($corp, $tag_id, $customerModel->get('id'));
                    }
                }
            }
            //记录 删除标签日志
            if (!empty($delTagList)){
                foreach($delTagList as $tag_id){
                    $delLogData=array_merge($baseLog,[
                        "tag_id"=>$tag_id,
                        "handle_type"=>EnumLogHandleType::DELETE,
                    ]);
                    CorpCstTagLogModel::create($delLogData);//写库
                    //更新标签客户
                    if ($status==EnumLogStatus::SUCCESS){
                        CustomerTagModel::removeCustomerTag($corp, $tag_id, $customerModel->get('id'));
                    }
                }
            }
            $transaction->commit();
        } catch (Throwable $e){
            $transaction->rollBack();
            throw new LogicException("操作数据库失败！事务回滚。原因：".$e->getMessage());
        }
    }
}
