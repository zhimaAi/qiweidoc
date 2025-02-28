<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\ChatStatisticSingle\Service;


use Common\Yii;
use Modules\Main\Enum\EnumChatMessageRole;
use Modules\Main\Model\ChatMessageModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\CustomersModel;
use Modules\Main\Model\StaffModel;
use Modules\ChatStatisticSingle\Model\ConfigModel;
use Yiisoft\Arrays\ArrayHelper;

/**
 * @author rand
 * @ClassName StatisticService
 * @date 2024/12/314:42
 * @description
 */
class StatisticService
{

    /**
     * @param CorpModel $corp
     * Notes: 获取单聊统计配置
     * User: rand
     * Date: 2024/12/2 10:07
     * @return array
     * @throws Throwable
     */
    public static function getConfig(CorpModel $corp)
    {
        $singleConfig = ConfigModel::query()->where(['corp_id' => $corp->get('id')])->getOne();

        if (empty($singleConfig)) {
            return [
                "work_time" => [
                    [
                        "week" => [1, 2, 3, 4, 5],
                        "range" => [
                            [
                                "s" => "09:00",
                                "e" => "19:00"
                            ]
                        ],
                    ]
                ],
                "cst_keywords" => [
                    "full" => ["好的", "谢谢"],
                    "half" => [],
                    "msg_type_filter" => []
                ],
                "staff_keywords" => [
                    "full" => [],
                    "half" => []
                ],
                "msg_reply_sec" => 3
            ];
        }

        return $singleConfig->toArray();
    }

    /**
     * @param CorpModel $corp
     * @param $param
     * Notes: 保存单聊统计配置
     * User: rand
     * Date: 2024/12/2 10:13
     * @return void
     */
    public static function saveConfig(CorpModel $corp, $param)
    {

        ConfigModel::updateOrCreate([
            "corp_id" => $corp->get("id")
        ], [
            "corp_id" => $corp->get('id'),
            "work_time" => $param["work_time"] ?? [],
            "cst_keywords" => $param["cst_keywords"] ?? [],
            "staff_keywords" => $param["staff_keywords"] ?? [],
            "msg_reply_sec" => $param["msg_reply_sec"] ?? 180,
        ]);
    }

    /**
     * @param CorpModel $corp
     * Notes: 获取单聊统计汇总数据
     * User: rand
     * Date: 2024/12/2 10:35
     * @return array
     */
    public static function getTotal(CorpModel $corp, $data)
    {

        $sql = " select
     sum(staff_msg_no_day) + sum(cst_msg_no_day) msg_no_day,
     sum(case when cst_msg_no_work > 0 then 1 else 0 end) as eft_chat_no,
     sum(first_recover_time) as first_recover_time,
     sum(recover_in_time) recover_in_time,
     sum(round_no) round_no,
     count(case when reply_status=0 then 1 else null end) as no_reply_chat_no,
     count(case when reply_status=1 then 1 else null end) as reply_no,
     count(date_no) chat_total
 from chat_statistic_single.detail where corp_id = '" . $corp->get("id") . "' ";

        $cstSql = " select count(id) as total from main.customers where corp_id = '" . $corp->get("id") . "' ";

        //存在指定日期
        if (!empty($data["stat_time"])) {
            $sql .= " and date_no = " . date("Ymd", $data["stat_time"]);
            $cstSql .= " and add_time >= '" . date("Y-m-d H:i:s", $data["stat_time"]) . "' and add_time < '" . date("Y-m-d", $data["stat_time"]) . " 23:59:59" . "'";
        }

        if (!empty($data["staff_userid"])) {
            $sql .= " and staff_user_id in ('" . implode("','", $data["staff_userid"]) . "') ";
            $cstSql .= " and staff_userid in ('" . implode("','", $data["staff_userid"]) . "') ";
        }

        $totalRes = Yii::db()->createCommand($sql)->queryAll()[0] ?? [];

        if (!empty($totalRes)) {
            $totalRes["rate_avg"] = $totalRes["round_no"] > 0 ? round($totalRes["recover_in_time"] / $totalRes["round_no"], 4) : 0;
            $totalRes["noreply_rate"] = $totalRes["eft_chat_no"] > 0 ? round($totalRes["no_reply_chat_no"] / $totalRes["eft_chat_no"], 4) : 0;
            $totalRes["time_st_avg"] = $totalRes["reply_no"] > 0 ? round($totalRes["first_recover_time"] / $totalRes["reply_no"], 0) : 0;
        }

        $totalRes["new_cst_total"] = Yii::db()->createCommand($cstSql)->queryAll()[0]["total"] ?? 0;

        return $totalRes;

    }


    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 获取单聊统计列表
     * User: rand
     * Date: 2024/12/20 18:16
     * @return array
     * @throws \Throwable
     * @throws \Yiisoft\Db\Exception\Exception
     * @throws \Yiisoft\Db\Exception\InvalidConfigException
     */
    public static function getList(CorpModel $corp, $data)
    {

        $page = max($data['page'] ?? 1, 1);
        $size = max($data['size'] ?? 10, 1);
        $offset = ($page - 1) * $size;

        $sql = " select
     sum(staff_msg_no_day) + sum(cst_msg_no_day) msg_no_day,
     sum(case when cst_msg_no_work > 0 then 1 else 0 end) as eft_chat_no,
     sum(first_recover_time) as first_recover_time,
     sum(recover_in_time) as recover_in_time,
     sum(round_no) as round_no,
     sum(cst_msg_no_day) as cst_msg_no_day,
     sum(staff_msg_no_day) as staff_msg_no_day,
     sum(cst_msg_no_work) as cst_msg_no_work,
     sum(staff_msg_no_work) as staff_msg_no_work,
     count(case when promoter_type = 1 then 1 else null end) as promoter_cst_no,
     count(case when promoter_type = 2 then 1 else null end) as promoter_stf_no,
     count(case when promoter_type = 1 and staff_msg_no_day > 0 then 1 else null end) as promoter_cst_no_valid,
     count(case when promoter_type = 2 and cst_msg_no_day > 0 then 1 else null end) as promoter_stf_no_valid,
     count(case when reply_status=0 then 1 else null end) as no_reply_chat_no,
     count(case when reply_status=1 then 1 else null end) as reply_no,
     count(date_no) chat_total,
     staff_user_id
 from chat_statistic_single.detail where ";

        $where = " corp_id = '" . $corp->get("id") . "' ";

        //存在统计日
        if (!empty($data["stat_time"])) {
            $where .= " and date_no = " . date("Ymd", $data["stat_time"]);
        }

        if (!empty($data["staff_userid"])) {
            $where .= " and staff_user_id in ('" . implode("','", $data["staff_userid"]) . "')";
        }

        $countSql = " select count(distinct staff_user_id) as total from chat_statistic_single.detail where " . $where;
        $count = Yii::db()->createCommand($countSql)->queryAll()[0]["total"] ?? 0;

        $orderBy = " order by a.msg_no_day DESC,a.staff_user_id DESC";
        if (!empty($data["order"])) {
            $orderBy = " order by " . $data["order"];
        }
        $groupBy = " group by staff_user_id ";
        $limit = " offset {$offset} limit {$size}";


        $list = Yii::db()->createCommand("select a.*,
       case when a.round_no >0 then round(cast(a.recover_in_time as numeric)/cast(a.round_no as numeric),4) else 0 end as rate_avg,
       case when a.eft_chat_no >0 then round(cast(a.no_reply_chat_no as numeric)/cast(a.eft_chat_no as numeric),4) else 0 end as noreply_rate,
       case when a.reply_no >0 then round(cast(a.first_recover_time as numeric)/cast(a.reply_no as numeric)) else 0 end as time_st_avg
       from ( " . $sql . $where . $groupBy . " ) as a " . $orderBy . $limit)->queryAll() ?? [];

        if (empty($list)) {
            return [];
        }

        $staffUserId = array_column($list, "staff_user_id");

        $cstTotalQuery = CustomersModel::query()->select(["count(id) as total", "staff_userid"])->where([
            "corp_id" => $corp->get("id")
        ])->andWhere(["in", "staff_userid", $staffUserId]);


        //总员工数
        $cstTotal = $cstTotalQuery->groupBy("staff_userid")->getAll()->toArray() ?? [];
        $cstTotalIndex = ArrayHelper::index($cstTotal, "staff_userid");


        //新客户数
        $statCstTotal = $cstTotalQuery->andWhere([">=", "add_time", date("Y-m-d H:i:s", $data["stat_time"])])->andWhere(["<", "add_time", date("Y-m-d", $data["stat_time"]) . " 23:59:59"])->groupBy("staff_userid")->getAll()->toArray() ?? [];
        $statCstTotalIndex = ArrayHelper::index($statCstTotal, "staff_userid");

        //员工信息
        $staffInfo = StaffModel::query()->select(["name", "userid"])->where(["corp_id" => $corp->get("id")])->andWhere(["in", "userid", $staffUserId])->getAll()->toArray() ?? [];
        $staffInfoIndex = ArrayHelper::index($staffInfo, "userid");

        foreach ($list as &$item) {
            $item["new_cst_total"] = $statCstTotalIndex[$item["staff_user_id"] ?? ""]["total"] ?? 0;
            $item["cst_total"] = $cstTotalIndex[$item["staff_user_id"] ?? ""]["total"] ?? 0;
            $item["staff_name"] = $staffInfoIndex[$item["staff_user_id"] ?? ""]["name"] ?? "";
        }

        $configInfo = ConfigModel::query()->where(["corp_id" => $corp->get("id")])->getOne();
        $last_stat_time = 0;
        if (!empty($configInfo)) {
            $last_stat_time = $configInfo->get("last_stat_time");
        }

        return compact("list", "count", "last_stat_time");


    }


    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 统计明细
     * User: rand
     * Date: 2024/12/23 15:38
     * @return array
     */
    public static function getDetail(CorpModel $corp, $data)
    {

        $page = max($data['page'] ?? 1, 1);
        $size = max($data['size'] ?? 10, 1);

        $offset = ($page - 1) * $size;

        $sql = " select
     staff_msg_no_day,
     cst_msg_no_day,
     first_recover_time,
     recover_in_time,
     round_no,
     promoter_type,
     reply_status,
     staff_user_id,
     conversation_id,
     last_msg_id,
     external_userid
 from chat_statistic_single.detail where ";

        $where = " corp_id = '" . $corp->get("id") . "'  and date_no = " . date("Ymd", $data["stat_time"]) . " and staff_user_id = '" . $data["staff_userid"] . "' ";

//        存在客户昵称搜索
        if (!empty($data["keyword"])) {
            $customerList = CustomersModel::query()->where([
                "corp_id" => $corp->get("id"),
                "staff_userid" => $data["staff_userid"]
            ])->andWhere(["ilike", "external_name", $data["keyword"]])->getAll();

            if (!empty($customerList)) {
                $externalUserids = array_column($customerList->toArray(), "external_userid");
                $where .= " and external_userid in ('" . implode("','", $externalUserids) . "')";
            }

        }

        switch ($data["type"] ?? "") {
            case "promoter_cst_no"://被动沟通
                $where .= " and promoter_type = " . EnumChatMessageRole::Customer->value;
                break;
            case "promoter_stf_no"://主动沟通
                $where .= " and promoter_type = " . EnumChatMessageRole::Staff->value;
                break;
            case "promoter_cst_no_valid"://被动有效沟通
                $where .= " and promoter_type = " . EnumChatMessageRole::Customer->value . " and staff_msg_no_day > 0 ";
                break;
            case "promoter_stf_no_valid"://主动有效沟通
                $where .= " and promoter_type = " . EnumChatMessageRole::Staff->value . " and cst_msg_no_day > 0 ";
                break;
            case "no_replay"://未回复
                $where .= " and reply_status = 0 ";
                break;
            case "is_new_user"://新客户
                $where .= " and is_new_user = 1 ";
                break;
        }


        $countSql = " select count(distinct external_userid) as total from chat_statistic_single.detail where " . $where;
        $count = Yii::db()->createCommand($countSql)->queryAll()[0]["total"] ?? 0;

        $orderBy = " order by a.staff_msg_no_day DESC,a.external_userid DESC";
        if (!empty($data["order"])) {
            $orderBy = " order by  " . $data["order"];
        }
        $limit = " offset {$offset} limit {$size}";


        $list = Yii::db()->createCommand("select a.*,case when a.round_no >0 then round(cast(a.recover_in_time as numeric)/cast(a.round_no as numeric),4) else 0 end as rate_avg  from (" . $sql . $where . " ) as a " . $orderBy . $limit)->queryAll() ?? [];

        if (empty($list)) {
            return [];
        }


        $externalUserids = array_column($list, "external_userid");

        $cstInfo = CustomersModel::query()->select(["staff_userid", "external_name", "avatar", "corp_name", "staff_remark", "external_userid", "add_time"])->where([
            "corp_id" => $corp->get("id"),
            "staff_userid" => $data["staff_userid"]
        ])->andWhere(["in", "external_userid", $externalUserids])->getAll()->toArray();

        $cstInfoIndex = ArrayHelper::index($cstInfo, "external_userid");

//        会话消息内容
        $lastMsgId = array_column($list, "last_msg_id");
        $msgInfo = ChatMessageModel::query()->where(["corp_id" => $corp->get("id")])->andWhere(["in", "msg_id", $lastMsgId])->getAll()->toArray();
        $msgInfoIndex = ArrayHelper::index($msgInfo, "msg_id");


        foreach ($list as &$item) {
            $item["rate_avg"] = round($item["rate_avg"], 4);
            $item["external_info"] = $cstInfoIndex[$item["external_userid"] ?? ""] ?? [];
            $item["msg_info"] = $msgInfoIndex[$item["last_msg_id"] ?? ""] ?? [];
        }

        $configInfo = ConfigModel::query()->where(["corp_id" => $corp->get("id")])->getOne();
        $last_stat_time = 0;
        if (!empty($configInfo)) {
            $last_stat_time = $configInfo->get("last_stat_time");
        }


        return compact("list", "count", "last_stat_time");

    }


}
