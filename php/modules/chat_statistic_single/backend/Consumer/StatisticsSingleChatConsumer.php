<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\ChatStatisticSingle\Consumer;


use Modules\Main\Enum\EnumChatConversationType;
use Modules\Main\Enum\EnumChatMessageRole;
use Modules\Main\Enum\EnumMessageType;
use Modules\Main\Model\ChatConversationsModel;
use Modules\Main\Model\ChatMessageModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\CustomersModel;
use Modules\ChatStatisticSingle\Model\ConfigModel;
use Modules\ChatStatisticSingle\Model\DetailModel;

/**
 * @author rand
 * @ClassName StatisticsHintConsumer
 * @date 2024/12/611:26
 * @description 单聊统计
 */
class StatisticsSingleChatConsumer
{

    private readonly CorpModel $corp;

    public function __construct($corp)
    {
        $this->corp = $corp;
    }

    public function handle()
    {

        //获取单聊统计规则
        $configInfo = ConfigModel::query()->where([
            "corp_id" => $this->corp->get("id")
        ])->getOne();

        if (empty($configInfo)) {
            return;
        }

        //    获取当日有会话数据
        $query = ChatConversationsModel::query()->where(["corp_id" => $this->corp->get("id"), "type" => EnumChatConversationType::Single->value]);
        $stat_time = strtotime(date("Y-m-d", time()) . " 00:00:00");

        //如果是凌晨2点前的统计，统计昨日的数据
        $thisHour = (int) date("h", time());
        if ($thisHour < 2) {
            $stat_time -= 86400;
        }

        $end_time = $stat_time + 86400;

        $baseTime = date("Y-m-d H:i:s", $stat_time);

        $page = 1;
        $size = 100;
        while (true) {
            $res = $query->andWhere([">", "updated_at", $baseTime])->orderBy(["updated_at" => SORT_ASC])->paginate($page, $size);

            if (count($res["items"]) == 0) {
                break;
            }
            foreach ($res["items"] as $item) {
                $baseTime = $item->get("updated_at");

                $staff_userid = "";
                $external_userid = "";
                if ($item->get("from_role") == EnumChatMessageRole::Customer) {
                    $external_userid = $item->get("from");
                    $staff_userid = $item->get("to");
                } else if ($item->get("from_role") == EnumChatMessageRole::Staff) {
                    $external_userid = $item->get("to");
                    $staff_userid = $item->get("from");
                } else {
                    continue;
                }


                //进行统计
                self::statistic($this->corp, $configInfo->toArray(), $staff_userid, $external_userid, $stat_time, $end_time, $item->get("id"));

            }

            $page++;

        }

        ConfigModel::query()->where([
            "corp_id" => $this->corp->get("id")
        ])->update([
            "last_stat_time" => time()
        ]);


    }

    /**
     * @param $corp
     * @param $rule
     * @param $staff_userid
     * @param $external_userid
     * @param $stat_time
     * @param $end_time
     * @param $conversation_id
     * Notes:
     * User: rand
     * Date: 2024/12/20 09:20
     * @return void
     */
    public static function statistic($corp, $rule, $staff_userid, $external_userid, $stat_time, $end_time, $conversation_id)
    {

        //查询对应会话今日的聊天记录
        $msgListQuery = ChatMessageModel::query()->where([
            "corp_id" => $corp->get("id"),
            "conversation_id" => $conversation_id,
        ]);


        $msg_reply_sec = $rule['msg_reply_sec'] ?? 3;
        $recover_in_time = 0;//指定时间内回复轮次
        $staff_msg_no_day = 0;//员工消息数
        $cst_msg_no_day = 0;//客户消息数
        $staff_msg_no_work = 0;//员工消息数(工作时间内)
        $cst_msg_no_work = 0;//客户消息数(工作时间内)
        $promoter_type = -1; //发起人类型 -1 无人 1 员工 2 客户
        $reply_status = -1; //回复状态 0 未回复，1:已回复
        $round_no = 0;//会话轮次数
        $round_flag = false; // 这个用来证明当前消息是一轮开始的 并且一定是一轮开始的第一条消息 用来计算 round_no
        $cst_msg_base_time = 0;//客户首轮对话时间
        $first_recover_time = 0;//首轮响应时间
        $round_msg_time = 0;//每一轮消息的时间
        $last_msg_id = "";//最后一条消息

        $date_no = date("Ymd", $stat_time);
        $is_new_user = 0;//是否为新客户

        $customerInfo = CustomersModel::query()->where([
            "corp_id" => $corp->get("id"),
            "staff_userid" => $staff_userid,
            "external_userid" => $external_userid
        ])->getOne();


        if (!empty($customerInfo)) {
            $is_new_user = date("Ymd", strtotime($customerInfo->get("add_time"))) == $date_no ? 1 : 0;
        }

        $msg_time = date("Y-m-d H:i:s", $stat_time);
        $msg_time_end = date("Y-m-d H:i:s", $end_time);
        $page = 1;
        $size = 100;
        while (true) {
            $msgList = $msgListQuery->andWhere([">", "msg_time", $msg_time])->andWhere(["<", "msg_time", $msg_time_end])->orderBy(["msg_time" => SORT_ASC])->paginate($page, $size);

            if (count($msgList["items"]) == 0) {
                break;
            }

            foreach ($msgList["items"] as $msg) {
                $msgInfo = $msg->toArray();

                //验证消息是否在工作时间内
                $at_work_time = self::checkWorkTime($rule["work_time"] ?? [], $msgInfo["msg_time"]);
                $has_hit_keyword = self::checkCstMsgKeyword($msgInfo, $rule["cst_keywords"]["full"] ?? [], $rule["cst_keywords"]["half"] ?? [], $rule["cst_keywords"]["msg_type_filter"] ?? []);
                $staff_msg_has_hit_keyword = self::checkStfMsgKeyword($msgInfo, $rule["staff_keywords"]["full"] ?? [], $rule["staff_keywords"]["half"] ?? []);


                //当日会话消息发起人
                if ($promoter_type == -1) {
                    $promoter_type = $msgInfo["from_role"]->value;
                }


                if ($msgInfo["from_role"] == EnumChatMessageRole::Staff) {//员工
                    $staff_msg_no_day++;

                    if ($at_work_time) {//工作时间内消息
                        $staff_msg_no_work++;
                    }
                }
                if ($msgInfo["from_role"] == EnumChatMessageRole::Customer) {//客户
                    $cst_msg_no_day++;

                    if ($at_work_time) {//工作时间内消息
                        $cst_msg_no_work++;
                    }
                }


//                客户发送的，且在工作时间内
                if ($msgInfo["from_role"] == EnumChatMessageRole::Customer and $at_work_time) {

//                    没有标记会话轮次，且 没有命中关键字
                    if (empty($round_flag) && empty($has_hit_keyword)) {
                        $round_flag = true;
                        $round_no++;
                        $round_msg_time = strtotime($msgInfo["msg_time"]);
                    }

//                    没有命中关键字，标记为未回复
                    if (empty($has_hit_keyword)) {
                        $reply_status = 0;
                        $last_msg_id = $msgInfo["msg_id"] ?? "";
                    }

//                    第一轮对话，没有命中关键词，在工作时间内
                    if ($round_no == 1 && empty($has_hit_keyword) && $at_work_time && $cst_msg_base_time == 0) {
                        $cst_msg_base_time = strtotime($msgInfo["msg_time"]);
                    }
                }

//                第一轮对话，发送方是员工，在工作时间内，计算首轮响应时间
                if ($round_no == 1 && $msgInfo["from_role"] == EnumChatMessageRole::Staff && $at_work_time && $first_recover_time == 0) {
                    $first_recover_time = strtotime($msgInfo["msg_time"]) - $cst_msg_base_time;
                }

//                员工发送的，且，不是新轮次,标记为已回复，轮次刷新
                if (!empty($round_flag) && $msgInfo["from_role"] == EnumChatMessageRole::Staff && empty($staff_msg_has_hit_keyword)) {
                    $reply_status = 1;
                    $round_flag = false;

//                    计算这一轮的回复时间

                    $round_replay_time = 0;
//                    如果存在当前轮次消息时间，计算一次本轮回复时间
                    if ($round_msg_time != 0) {
                        $round_replay_time = strtotime($msgInfo["msg_time"]) - $round_msg_time;
                        $round_msg_time = 0;
                    }
                    if ($round_replay_time < $msg_reply_sec * 60) {
                        $recover_in_time++;
                    }
                }
            }

            $page++;
        }

        $where = [
            "corp_id" => $corp->get("id"),
            "date_no" => $date_no,
            "stat_time" => date("Y-m-d H:i:s", $stat_time),
            "staff_user_id" => $staff_userid,
            "external_userid" => $external_userid,
        ];

        $updateData = [
            "staff_msg_no_day" => $staff_msg_no_day,
            "cst_msg_no_day" => $cst_msg_no_day,
            "staff_msg_no_work" => $staff_msg_no_work,
            "cst_msg_no_work" => $cst_msg_no_work,
            "round_no" => $round_no,
            "recover_in_time" => $recover_in_time,
            "first_recover_time" => $first_recover_time,
            "reply_status" => $reply_status,
            "promoter_type" => $promoter_type,
            "last_msg_id" => $last_msg_id,
            "is_new_user" => $is_new_user,
            "conversation_id" => $conversation_id,
        ];

        DetailModel::updateOrCreate($where, array_merge($updateData, $where));

    }


    /**
     * @param $time_range
     * @param $msg_timestamp
     * Notes: 验证消息时间是否在工作时间内
     * User: rand
     * Date: 2024/12/20 15:26
     * @return bool
     */
    public static function checkWorkTime($time_range, $msg_timestamp): bool
    {
        $msg_time = strtotime($msg_timestamp);
        if (empty($time_range)) {
            return false;
        }

        $w = date("w", $msg_time);
        $H = date("H:i", $msg_time);
        foreach ($time_range as $i => $one) {
            $week = $one["week"] ?? [];
            $range = $one["range"] ?? [];

            if (!in_array($w, $week)) {
                continue;
            }
            foreach ($range as $ii => $v) {
                $s = $v["s"] ?? 0;
                $e = $v["e"] ?? 0;
                if ($H >= $s and $H <= $e) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param $msg
     * @param $full
     * @param $half
     * @param $msg_type_filter
     * Notes: 验证消息是否匹配到关键字
     * User: rand
     * Date: 2024/12/20 15:29
     * @return bool
     */
    public static function checkCstMsgKeyword($msg, $full, $half, $msg_type_filter)
    {

        //emoji
        if (in_array("emotion", $msg_type_filter) && $msg["msg_type"] == EnumMessageType::Emotion->value) {
            return true;
        }
        //图片
        if (in_array("image", $msg_type_filter) && $msg["msg_type"] == EnumMessageType::Image->value) {
            return true;
        }

        if (empty($msg['msg_type']) || $msg['msg_type'] != 'text') {
            return false;
        }

        //表情
        if (in_array("emoji_preg", $msg_type_filter) && isWeChatEmoji($msg["msg_content"])) {
            return true;
        }


        $msg_content = $msg["raw_content"]["content"] ?? "";
        foreach ($full as $word) {
            if ($msg_content === $word) {
                return true;
            }
        }

        foreach ($half as $word) {
            if (strpos($msg_content, $word) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $msg
     * @param $full
     * @param $half
     * Notes: 员工消息是否命中关键词
     * User: rand
     * Date: 2024/12/26 15:26
     * @return bool
     */
    public static function checkStfMsgKeyword($msg, $full, $half)
    {

        if (empty($msg['msg_type']) || $msg['msg_type'] != 'text') {
            return false;
        }


        $msg_content = $msg["raw_content"]["content"] ?? "";
        foreach ($full as $word) {
            if ($msg_content === $word) {
                return true;
            }
        }

        foreach ($half as $word) {
            if (strpos($msg_content, $word) !== false) {
                return true;
            }
        }
        return false;
    }

}
