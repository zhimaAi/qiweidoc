<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\HintKeywords\Service;


use Common\DB\BaseModel;
use Common\Yii;
use Modules\HintKeywords\Model\HintKeywordsModel;
use Modules\HintKeywords\Model\NoticeConfig;
use Modules\HintKeywords\Model\RuleModel;
use Modules\Main\Enum\EnumChatConversationType;
use Modules\Main\Enum\EnumChatMessageRole;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\CustomersModel;
use Modules\Main\Model\GroupModel;
use Modules\Main\Model\StaffModel;
use Modules\Main\Model\UserModel;
use Yiisoft\Arrays\ArrayHelper;

/**
 * @author rand
 * @ClassName StatisticService
 * @date 2024/12/314:42
 * @description
 */
class IndexService
{


    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 敏感词列表
     * User: rand
     * Date: 2024/12/3 14:55
     * @return array
     * @throws \Throwable
     */
    public static function list(CorpModel $corp, $data): array
    {
        $query = HintKeywordsModel::query()->where(['corp_id' => $corp->get('id')]);

        $res = $query->orderBy(['id' => SORT_DESC])->paginate($data['page'] ?? 1, $data['size'] ?? 10);

        $createUserIds = [];
        foreach ($res["items"] as &$item) {
            $item->set("created_at",date("Y-m-d H:i:s",strtotime($item->get("created_at"))));
            if ($item->get("create_user_id") != "") {
                $createUserIds[] = $item->get("create_user_id");
            }
        }

        if (!empty($createUserIds)) {
            $allStaffUserInfo = StaffModel::query()->select(["userid", "name"])->where(['corp_id' => $corp->get('id')])->andWhere(["in", "userid", $createUserIds])->getAll()->toArray();
            $allStaffUserInfoIndex = ArrayHelper::index($allStaffUserInfo, "userid");

            foreach ($res["items"] as &$item) {
                $item->append("create_user_name", $allStaffUserInfoIndex[$item->get("create_user_id") ?? ""]["name"] ?? "");
            }
        }


        return $res;
    }

    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 删除敏感词组
     * User: rand
     * Date: 2024/12/10 11:01
     * @return void
     * @throws \Throwable
     */
    public static function delete(CorpModel $corp, $data)
    {
        HintKeywordsModel::query()->where(["corp_id" => $corp->get("id"), "id" => $data["id"] ?? 0])->deleteAll();
        return;
    }

    /**
     * @param CorpModel $corp
     * @param UserModel $currentUser
     * @param $data
     * Notes: 保存敏感词
     * User: rand
     * Date: 2024/12/3 15:08
     * @return void
     * @throws \Throwable
     */
    public static function save(CorpModel $corp, UserModel $currentUser, $data): void
    {

        if (empty($data["group_name"])) {
            throw new \Exception("敏感词组名不能为空");
        }

        $updateData = [
            "group_name" => $data["group_name"] ?? "",
            "keywords" => array_values(array_unique(array_filter($data["keywords"] ?? []))),
        ];

        $hisQuery = HintKeywordsModel::query()->where(["corp_id" => $corp->get("id"), "group_name" => $data["group_name"]]);

        if (!empty($data["id"])) {
            $hisQuery->andWhere(["!=", "id", $data["id"]]);
        }

        $hisData = $hisQuery->getOne();
        if (!empty($hisData)) {
            throw new \Exception("敏感词组名已存在");
        }

        if (!empty($data["id"])) {
            HintKeywordsModel::updateOrCreate(
                ['corp_id' => $corp->get('id'), "id" => $data["id"]],
                $updateData
            );

            return;
        }

        $updateData["create_user_id"] = $currentUser->get("userid");


        HintKeywordsModel::create(array_merge([
            "corp_id" => $corp->get('id'),
        ], $updateData));

    }

    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 规则列表
     * User: rand
     * Date: 2024/12/4 11:49
     * @return array
     * @throws \Throwable
     */
    public static function ruleList(CorpModel $corp, $data): array
    {
        $query = RuleModel::query()->where(['corp_id' => $corp->get('id')]);

        $res = $query->orderBy(['id' => SORT_DESC])->paginate($data['page'] ?? 1, $data['size'] ?? 10);


        $allStaffUserIds = [];
        foreach ($res["items"] as &$item) {
            $item->set("created_at",date("Y-m-d H:i:s",strtotime($item->get("created_at"))));
            if ($item->get("create_user_id") != "") {
                $allStaffUserIds[] = $item->get("create_user_id");
            }
        }

        if (!empty($allStaffUserIds)) {
            $allStaffUserInfo = StaffModel::query()->select(["userid", "name"])->where(['corp_id' => $corp->get('id')])->andWhere(["in", "userid", array_values(array_unique($allStaffUserIds))])->getAll()->toArray();
            $allStaffUserInfoIndex = ArrayHelper::index($allStaffUserInfo, "userid");

            foreach ($res["items"] as &$item) {
                $item->append("create_user_name", $allStaffUserInfoIndex[$item->get("create_user_id") ?? ""]["name"] ?? "");
            }
        }

        return $res;
    }

    /**
     * @param CorpModel $corp
     * @param $rule_id
     * Notes: 敏感词统计
     * User: rand
     * Date: 2024/12/3 17:09
     * @return array
     */
    public static function ruleStatistics(CorpModel $corp, $rule_id, $start_time, $end_time): array
    {

        $whereSql = " corp_id = '" . $corp->get("id") . "' ";

        if (!empty($rule_id)) {
            $whereSql .= " and rule_id = " . $rule_id;
        }

        //全部规则的汇总
        $totalSql = /** @lang sql */
            <<<SQL
select
     COALESCE(sum(statistic_staff_keywords),0) as statistic_staff_keywords,
     COALESCE(sum(statistic_staff_msg),0) as statistic_staff_msg,
     COALESCE(sum(statistic_cst_keywords),0) as statistic_cst_keywords,
     COALESCE(sum(statistic_cst_msg),0) as statistic_cst_msg
 from hint_keywords.rule where {$whereSql};
SQL;

        $totalData = Yii::db()->createCommand($totalSql)->queryAll()[0] ?? [];


        //指定日期的汇总
        if (!empty($start_time) && !empty($end_time)) {
            $whereSql .= " and msg_time >= '" . date("Y-m-d H:i:s", $start_time) . "' and msg_time < '" . date("Y-m-d H:i:s", $end_time) . "' ";
        }

        $dayTotalSql = /** @lang sql */
            <<<SQL
select
     count(msg_id) as total,hint_type,from_role
 from hint_keywords.detail where {$whereSql} group by hint_type,from_role;
SQL;

        $res = Yii::db()->createCommand($dayTotalSql)->queryAll();

        $dayTotalData = [
            "statistic_staff_keywords" => 0,
            "statistic_staff_msg" => 0,
            "statistic_cst_keywords" => 0,
            "statistic_cst_msg" => 0,
        ];

        foreach ($res as $node) {
            if ($node["hint_type"] == 1) {//敏感词

                if ($node["from_role"] == EnumChatMessageRole::Customer->value) {
                    $dayTotalData["statistic_cst_keywords"] += $node["total"] ?? 0;
                } else if ($node["from_role"] == EnumChatMessageRole::Staff->value) {
                    $dayTotalData["statistic_staff_keywords"] += $node["total"] ?? 0;
                }
            } else {//敏感行为
                if ($node["from_role"] == EnumChatMessageRole::Customer->value) {
                    $dayTotalData["statistic_cst_msg"] += $node["total"] ?? 0;
                } else if ($node["from_role"] == EnumChatMessageRole::Staff->value) {
                    $dayTotalData["statistic_staff_msg"] += $node["total"] ?? 0;
                }
            }
        }


        return compact("totalData", "dayTotalData");
    }

    /**
     * @param CorpModel $corp
     * @param UserModel $currentUser
     * @param $data
     * Notes: 保存敏感词规则
     * User: rand
     * Date: 2024/12/3 17:09
     * @return void
     * @throws \Throwable
     */
    public static function saveRule(CorpModel $corp, UserModel $currentUser, $data): void
    {

        $updateData = [
            "rule_name" => $data["rule_name"] ?? "未命名规则",
            "chat_type" => $data["chat_type"] ?? 0,
            "group_chat_type" => $data["group_chat_type"] ?? 1,
            "check_user_type" => $data["check_user_type"] ?? 0,
            "hint_group_ids" => $data["hint_group_ids"] ?? [],
            "hint_keywords" => $data["hint_keywords"] ?? [],
            "target_msg_type" => $data["target_msg_type"] ?? ["text"],
            "group_chat_id" => $data["group_chat_id"] ?? [],
        ];
        if (!empty($data["id"])) {
            RuleModel::updateOrCreate(
                ['corp_id' => $corp->get('id'), "id" => $data["id"]],
                $updateData
            );

            return;
        }

        $updateData["create_user_id"] = $currentUser->get("userid");

        RuleModel::create(array_merge([
            "corp_id" => $corp->get('id'),
        ], $updateData));

    }

    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 变更规则启用状态
     * User: rand
     * Date: 2024/12/6 15:11
     * @return void
     * @throws \Throwable
     */
    public static function changeStatus(CorpModel $corp, $data)
    {

        RuleModel::query()->where(["corp_id" => $corp->get("id"), "id" => $data["id"]])->update([
            "switch_status" => $data["switch_status"]
        ]);

    }

    /**
     * @param CorpModel $corp
     * @param $rule_id
     * Notes: 获取单个规则详情
     * User: rand
     * Date: 2024/12/3 17:30
     * @return array
     * @throws \Throwable
     */
    public static function ruleInfo(CorpModel $corp, $rule_id): array
    {

        $ruleInfo = RuleModel::query()->where(['corp_id' => $corp->get('id'), "id" => $rule_id])->getOne()->toArray();

        if (!empty($ruleInfo["group_chat_id"])) {
            $ruleInfo["group_chat_info"] = GroupModel::query()->select(["name", "chat_id"])->where(['corp_id' => $corp->get('id')])->andWhere(["in", "chat_id", $ruleInfo["group_chat_id"]])->getAll()->toArray();
        }

        if (!empty($ruleInfo["hint_group_ids"])) {
            $ruleInfo["hint_group_info"] = HintKeywordsModel::query()->where(['corp_id' => $corp->get('id')])->andWhere(["in", "id", $ruleInfo["hint_group_ids"]])->getAll()->toArray();
        }

        return $ruleInfo;
    }


    /**
     * @param CorpModel $corp
     * @param $param
     * Notes: 敏感词触发明细
     * User: rand
     * Date: 2024/12/10 10:08
     * @return array
     * @throws \Throwable
     * @throws \Yiisoft\Db\Exception\Exception
     * @throws \Yiisoft\Db\Exception\InvalidConfigException
     */
    public static function ruleDetail(CorpModel $corp, $param)
    {

        $page = $param["page"] ?? 1;
        $size = $param["size"] ?? 10;
        $offset = ($page - 1) * $size;

        $start_time = $param["start_time"] ?? "";
        $end_time = $param["end_time"] ?? "";

        $whereSql = " corp_id = '{$corp->get("id")}'  ";

        //存在筛选时间
        if (!empty($end_time) && !empty($start_time)) {
            $whereSql .= "  and msg_time >= '{$start_time}' and msg_time < '{$end_time}' ";
        }

        //指定规则ID
        if (!empty($param["rule_id"])) {
            $whereSql .= " and rule_id = {$param["rule_id"]} ";
        }

        //类型，1:敏感词，2:敏感行为
        if (!empty($param["hint_type"])) {
            $whereSql .= " and hint_type = {$param["hint_type"]} ";
        }


        //触发行为，枚举值
        if (!empty($param["target_msg_type"])) {
            $whereSql .= " and target_msg_type = '{$param["target_msg_type"]}' ";
        }

        //存在发送员工ID
        if (!empty($param["staff_userid"])) {
            $whereSql .= " and from_role = " . EnumChatMessageRole::Staff->value . " and from_user_id = '{$param["staff_userid"]}' ";
        }

        //存在发送客户ID
        if (!empty($param["external_username"])) {
            $cstInfo = CustomersModel::query()->select(["external_userid"])->where(["corp_id" => $corp->get("id")])->andWhere(['like', 'external_name', $param['external_username']])->limit(1000)->getAll()->toArray();

            $external_userid = array_values(array_unique(array_filter(array_column($cstInfo, "external_userid"))));

            $whereSql .= " and from_role = " . EnumChatMessageRole::Customer->value . " and from_user_id in ( '" . implode("','", $external_userid) . "') ";
        }

        if (!empty($param["keyword"])) {
            $whereSql .= " and msg_content like '%{$param["keyword"]}%' ";
        }


        //
        $listSql = /** @lang sql */
            <<<SQL
select * from detail where {$whereSql} order by msg_time DESC offset {$offset} limit {$size};
SQL;
        $countSql = /** @lang sql */
            <<<SQL
select count(*) from detail where {$whereSql};
SQL;

        $totalRes = Yii::db()->createCommand($countSql)->queryColumn()[0];
        $listRes = Yii::db()->createCommand($listSql)->queryAll();

        //遍历获取所有员工、客户、群信息
        $staffUserids = [];
        $cstUserids = [];
        $chatIds = [];
        foreach ($listRes as $item) {
            if ($item["from_role"] == 1) {
                $cstUserids[] = $item["from_user_id"] ?? "";
                $staffUserids[] = $item["to_user_id"] ?? "";
            } else {
                $staffUserids[] = $item["from_user_id"] ?? "";
                $cstUserids[] = $item["to_user_id"] ?? "";
            }

            if ($item["conversation_type"] == 2) {
                $chatIds[] = $item["to_user_id"] ?? "";
            }
        }

        //员工信息
        $staffUserInfo = [];
        if (!empty($staffUserids)) {
            $staffUserInfo = StaffModel::query()->select(["name", "userid"])->where([
                "corp_id" => $corp->get("id"),
            ])->andWhere(["in", "userid", $staffUserids])->getAll()->toArray();
        }
        $staffUserInfoIndex = ArrayHelper::index($staffUserInfo, "userid");

        //客户信息
        $cstUserInfo = [];
        if (!empty($cstUserids)) {
            $cstUserInfo = CustomersModel::query()->select(["external_userid", "external_name", "avatar"])->where([
                "corp_id" => $corp->get("id"),
            ])->andWhere(["in", "external_userid", array_values(array_unique($cstUserids))])->getAll()->toArray();
        }
        $cstUserInfoIndex = ArrayHelper::index($cstUserInfo, "external_userid");

        //群信息
        $groupInfo = [];
        if (!empty($chatIds)) {
            $groupInfo = GroupModel::query()->select(["name", "chat_id","member_list"])->where([
                "corp_id" => $corp->get("id"),
            ])->andWhere(["in", "chat_id", $chatIds])->getAll()->toArray();
        }
        $groupInfoIndex = ArrayHelper::index($groupInfo, "chat_id");


        foreach ($listRes as &$item) {

            $item["msg_time"] = date("Y-m-d H:i:s",strtotime($item["msg_time"]));
            //群聊信息填充
            if ($item["conversation_type"] == EnumChatConversationType::Group->value) {
                $item["group_info"] = $groupInfoIndex[$item["to_user_id"] ?? ""] ?? [];
            }


            if ($item["conversation_type"] == EnumChatConversationType::Single->value) {//单聊

                if ($item["from_role"] == EnumChatMessageRole::Staff->value) {
                    $item["from_user_info"] = $staffUserInfoIndex[$item["from_user_id"] ?? ""] ?? [];
                    $item["to_user_info"] = $cstUserInfoIndex[$item["to_user_id"] ?? ""] ?? [];
                } else {
                    $item["from_user_info"] = $cstUserInfoIndex[$item["from_user_id"] ?? ""] ?? [];
                    $item["to_user_info"] = $staffUserInfoIndex[$item["to_user_id"] ?? ""] ?? [];
                }
            } else {//群聊

                if ($item["from_role"] == EnumChatMessageRole::Staff->value) {
                    $item["from_user_info"] = $staffUserInfoIndex[$item["from_user_id"] ?? ""] ?? [];
                } else if ($item["from_role"] == EnumChatMessageRole::Customer->value) {
                    $item["from_user_info"] = $cstUserInfoIndex[$item["from_user_id"] ?? ""] ?? [];
                }

                //如果还是没有发送人信息，群成员里面找一下
                if (empty($item["from_user_info"])) {
                    $item["from_role"] = 3;
                    $memberList = ArrayHelper::index($item["group_info"]["member_list"] ?? [], "userid");
                    $item["from_user_info"] = $memberList[$item["from_user_id"] ?? ""] ?? [];
                }
            }
        }


        return BaseModel::buildPaginate($page, $size, $totalRes, $listRes);

    }

    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 删除敏感词规则
     * User: rand
     * Date: 2024/12/10 11:04
     * @return void
     * @throws \Throwable
     */
    public static function ruleDelete(CorpModel $corp, $data)
    {

        RuleModel::query()->where(["corp_id" => $corp->get("id"), "id" => $data["id"]])->deleteAll();

        return;
    }


    /**
     * @param CorpModel $corp
     * Notes: 敏感词提醒通知配置
     * User: rand
     * Date: 2024/12/3 18:43
     * @return array
     */
    public static function noticeInfo(CorpModel $corp): array
    {
        $notice = NoticeConfig::query()->where(["corp_id" => $corp->get("id")])->getOne();

        if (empty($notice)) {
            return [];
        }

        $noticeInfo = $notice->toArray();

        $staffUserIds = array_merge($noticeInfo["app_notice_userid"] ?? [], $noticeInfo["wechat_notice_user"] ?? []);

        $noticeInfo["staff_user_info"] = [];

        if (!empty($staffUserIds)) {
            $noticeInfo["staff_user_info"] = StaffModel::query()->select(["id", "name", "userid"])->where(["corp_id" => $corp->get("id")])->andWhere(["in", "userid", $staffUserIds])->getAll()->toArray();
        }

        return $noticeInfo;
    }

    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 保存通知配置
     * User: rand
     * Date: 2024/12/6 17:47
     * @return void
     * @throws \Throwable
     */
    public static function noticeSave(CorpModel $corp, $data): void
    {

        $updateData = [
            "notice_switch" => $data["notice_switch"] ?? 0,
            "app_notice_switch" => $data["app_notice_switch"] ?? 0,
            "app_notice_userid" => $data["app_notice_userid"] ?? "[]",
            "wechat_notice_switch" => $data["wechat_notice_switch"] ?? 0,
            "wechat_notice_hook" => $data["wechat_notice_hook"] ?? "",
            "wechat_notice_type" => $data["wechat_notice_type"] ?? 0,
            "wechat_notice_user" => $data["wechat_notice_user"] ?? '[]',
            "dingtalk_notice_switch" => $data["dingtalk_notice_switch"] ?? 0,
            "dingtalk_notice_hook" => $data["dingtalk_notice_hook"] ?? "",
            "dingtalk_notice_secret" => $data["dingtalk_notice_secret"] ?? "",
            "dingtalk_notice_type" => $data["dingtalk_notice_type"] ?? 0,
            "dingtalk_notice_user" => $data["dingtalk_notice_user"] ?? '[]',
        ];

        if (!empty($data["id"])) {
            NoticeConfig::updateOrCreate(
                ['corp_id' => $corp->get('id'), "id" => $data["id"]],
                $updateData
            );

            return;
        }

        NoticeConfig::create(array_merge([
            "corp_id" => $corp->get('id'),
        ], $updateData));
    }


}
