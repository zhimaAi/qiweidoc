<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\ChatStatisticGroup\Service;

use Common\Yii;
use Modules\Main\Model\ChatMessageModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\GroupModel;
use Modules\Main\Model\StaffModel;
use Modules\ChatStatisticGroup\Model\ConfigModel;
use Throwable;
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
        $groupConfig = ConfigModel::query()->where(['corp_id' => $corp->get('id')])->getOne();

        if (empty($groupConfig)) {
            return [
                "work_time" => [
                    [
                        "week" => [1, 2, 3, 4, 5],
                        "range" => [
                            [
                                "s" => "09:00",
                                "e" => "18:00"
                            ]
                        ],
                    ]
                ],
                "cst_keywords" => [
                    "full" => ["好的", "谢谢"],
                    "half" => [],
                    "msg_type_filter" => []
                ],
                "msg_reply_sec" => 3,
                "at_msg_reply_sec" => 3,
                "other_effect" => 0,
            ];
        }

        $config = $groupConfig->toArray();

        //如果有指定员工，查一下数据
        if (!empty($config["group_staff_ids"])) {
            $config["group_staff_info"] = StaffModel::query()->select(["userid", "name"])->where([
                "corp_id" => $corp->get("id")
            ])->andWhere(["in", "userid", $config["group_staff_ids"]])->getAll()->toArray();
        }

        return $config;
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
            "other_effect" => $param["other_effect"] ?? 0,
            "group_staff_type" => $param["group_staff_type"] ?? 2,
            "group_staff_ids" => $param["group_staff_ids"] ?? [],
            "at_msg_reply_sec" => $param["at_msg_reply_sec"] ?? 3,
            "msg_reply_sec" => $param["msg_reply_sec"] ?? 3,
        ]);
    }

    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 获取单聊统计列表
     * User: rand
     * Date: 2024/12/20 18:16
     * @return array
     * @throws Throwable
     */
    public static function getList(CorpModel $corp, $data)
    {
        $page = max($data['page'] ?? 1, 1);
        $size = max($data['size'] ?? 10, 1);
        $offset = ($page - 1) * $size;

        $sql = /** @lang sql */
            " select
     sum(staff_msg_no_day) + sum(cst_msg_no_day) msg_no_day,
     sum(case when cst_msg_no_work > 0 then 1 else 0 end) as eft_chat_no,
     sum(recover_in_time) as recover_in_time,
     sum(staff_self_msg_num) as staff_self_msg_num,
     sum(round_no) as round_no,
     sum(at_recover_in_time) as at_recover_in_time,
     sum(at_round_no) as at_round_no,
     sum(cst_msg_no_day) as cst_msg_no_day,
     sum(staff_msg_no_day) as staff_msg_no_day,
     count(case when reply_status=0 then 1 else null end) as no_reply_chat_no,
     count(case when reply_status=1 then 1 else null end) as reply_no,
     count(case when is_new_room=1 then 1 else null end) as new_chat_total,
     count(distinct room_id) as chat_total,
     staff_user_id
 from chat_statistic_group.detail where ";

        $where = " corp_id = '" . $corp->get("id") . "' ";

        //存在统计日
        if (!empty($data["stat_time"])) {
            $where .= " and date_no = " . date("Ymd", $data["stat_time"]);
        }

        if (!empty($data["staff_userid"])) {
            $where .= " and staff_user_id in ('" . implode("','", $data["staff_userid"]) . "')";
        }

        $countSql = /** @lang sql */
            " select count(distinct staff_user_id) as total from chat_statistic_group.detail where " . $where;
        $count = Yii::db()->createCommand($countSql)->queryAll()[0]["total"] ?? 0;

        $orderBy = " order by a.msg_no_day DESC,a.staff_user_id DESC";
        if (!empty($data["order"])) {
            $orderBy = " order by " . $data["order"];
        }
        $groupBy = " group by staff_user_id ";
        $limit = " offset {$offset} limit {$size}";


        $listSql = /** @lang sql */
            "select a.*,
       case when a.round_no >0 then round(cast(a.recover_in_time as numeric)/cast(a.round_no as numeric),4) else 0 end as rate_avg,
       case when a.at_round_no >0 then round(cast(a.at_recover_in_time as numeric)/cast(a.at_round_no as numeric),4) else 0 end as at_rate_avg,
       case when a.eft_chat_no >0 then round(cast(a.no_reply_chat_no as numeric)/cast(a.eft_chat_no as numeric),4) else 0 end as noreply_rate
       from ( " . $sql . $where . $groupBy . " ) as a " . $orderBy . $limit;
        $list = Yii::db()->createCommand($listSql)->queryAll() ?? [];

        if (empty($list)) {
            return [];
        }

        $staffUserId = array_column($list, "staff_user_id");

        //员工信息
        $staffInfo = StaffModel::query()->select(["name", "userid"])->where(["corp_id" => $corp->get("id")])->andWhere(["in", "userid", $staffUserId])->getAll()->toArray() ?? [];
        $staffInfoIndex = ArrayHelper::index($staffInfo, "userid");

        foreach ($list as &$item) {
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

        $sql = /** @lang sql */
            " select
     staff_msg_no_day + cst_msg_no_day msg_no_day,
     staff_msg_no_day,
     cst_msg_no_day,
     staff_msg_no_work,
     cst_msg_no_work,
     promoter_type,
     reply_status,
     round_no,
     recover_in_time,
     at_round_no,
     at_recover_in_time,
     staff_user_id,
     conversation_id,
     last_msg_id,
     room_id,
     staff_self_msg_num
 from chat_statistic_group.detail where ";

        $where = " corp_id = '" . $corp->get("id") . "'  and date_no = " . date("Ymd", $data["stat_time"]) . " and staff_user_id = '" . $data["staff_userid"] . "' ";

//        存在客户昵称搜索
        if (!empty($data["keyword"])) {
            $groupLisg = GroupModel::query()->where([
                "corp_id" => $corp->get("id"),
            ])->andWhere(["ilike", "name", $data["keyword"]])->getAll();

            if (!empty($groupLisg)) {
                $chatIds = array_column($groupLisg->toArray(), "chat_id");
                $where .= " and room_id in ('" . implode("','", $chatIds) . "')";
            }

        }

        switch ($data["type"] ?? "") {
            case "active_room"://活跃群聊
                $where .= " and cst_msg_no_work > 0 ";
                break;
            case "rate_avg"://回复率
                $where .= " and staff_msg_no_day > 0 ";
                break;
            case "replay"://已回复
                $where .= " and reply_status = 1 ";
                break;
            case "no_replay"://未回复
                $where .= " and reply_status = 0 ";
                break;
            case "new_room"://今日新增群聊
                $where .= " and is_new_room = 1 ";
                break;
        }

        $countSql = /** @lang sql */
            " select count(distinct room_id) as total from chat_statistic_group.detail where " . $where;
        $count = Yii::db()->createCommand($countSql)->queryAll()[0]["total"] ?? 0;

        $orderBy = " order by a.staff_msg_no_day DESC,a.room_id DESC";
        if (!empty($data["order"])) {
            $orderBy = " order by  " . $data["order"];
        }
        $limit = " offset {$offset} limit {$size}";

        $listSql = /** @lang sql */
            "select a.*,
       case when a.round_no >0 then round(cast(a.recover_in_time as numeric)/cast(a.round_no as numeric),4) else 0 end as rate_avg,
       case when a.at_round_no >0 then round(cast(a.at_recover_in_time as numeric)/cast(a.at_round_no as numeric),4) else 0 end as at_rate_avg
from (" . $sql . $where . " ) as a " . $orderBy . $limit;
        $list = Yii::db()->createCommand($listSql)->queryAll() ?? [];

        if (empty($list)) {
            return [];
        }

        //群信息
        $chatIds = array_column($list, "room_id");
        $chatInfo = GroupModel::query()->select(["chat_id", "name", "owner"])->where([
            "corp_id" => $corp->get("id"),
        ])->andWhere(["in", "chat_id", $chatIds])->getAll()->toArray();
        $chatInfoIndex = ArrayHelper::index($chatInfo, "chat_id");

        //群主信息
        $ownerIds = array_column($chatInfo, "owner");
        $staffInfo = StaffModel::query()->select(["name", "userid"])->where([
            "corp_id" => $corp->get("id"),
        ])->andWhere(["in", "userid", $ownerIds])->getAll()->toArray();
        $staffInfoIndex = ArrayHelper::index($staffInfo, "userid");

//        会话消息内容
        $lastMsgId = array_column($list, "last_msg_id");
        $msgInfo = ChatMessageModel::query()->where(["corp_id" => $corp->get("id")])->andWhere(["in", "msg_id", $lastMsgId])->getAll()->toArray();
        $msgInfoIndex = ArrayHelper::index($msgInfo, "msg_id");

        foreach ($list as &$item) {
            $item["chat_info"] = $chatInfoIndex[$item["room_id"] ?? ""] ?? [];
            $item["owner_info"] = $staffInfoIndex[$item["chat_info"]["owner"] ?? ""] ?? [];
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
