<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\ChatStatisticGroup\Consumer;

use Modules\Main\Enum\EnumChatMessageRole;
use Modules\Main\Enum\EnumMessageType;
use Modules\Main\Model\ChatMessageModel;
use Modules\Main\Model\CorpModel;
use Modules\ChatStatisticGroup\Model\ConfigModel;
use Modules\ChatStatisticGroup\Model\DetailModel;
use Modules\Main\Model\GroupModel;
use Modules\Main\Model\StaffModel;
use Yiisoft\Arrays\ArrayHelper;

/**
 * Notes: 群聊统计消费者
 * User: rand
 * Date: 2024/12/27 14:51
 */
readonly class StatisticsGroupChatConsumer
{
    private CorpModel $corp;

    public function __construct($corp)
    {
        $this->corp = $corp;
    }

    public function handle()
    {
        //获取群聊统计规则
        $configInfo = ConfigModel::query()->where([
            "corp_id" => $this->corp->get("id")
        ])->getOne();

        if (empty($configInfo)) {
            return;
        }

        //获取会话存档员工列表
        $staffUserList = StaffModel::query()->where([
            "corp_id" => $this->corp->get("id"),
            "chat_status" => 1
        ])->getAll();

        //没有会话存档员工，退出
        if (empty($staffUserList)) {
            return;
        }

        $query = GroupModel::query()->where([
            "corp_id" => $this->corp->get("id")
        ]);

        $stat_time = strtotime(date("Y-m-d", time()) . " 00:00:00");

        //如果是凌晨2点前的统计，统计昨日的数据
        $thisHour = (int) date("h", time());
        if ($thisHour < 2) {
            $stat_time -= 86400;
        }

        $end_time = $stat_time + 86400;
        $baseId = 0;

        $page = 1;
        $size = 100;
        while (true) {
            $res = $query->andWhere([">", "id", $baseId])->orderBy(["id" => SORT_ASC])->paginate($page, $size);

            if (count($res["items"]) == 0) {
                break;
            }
            foreach ($res["items"] as $groupInfo) {
                $baseId = $groupInfo->get("id");
                //进行统计
                foreach ($staffUserList as $staffInfo) {//会话存档员工
                    self::statistic($this->corp, $configInfo->toArray(), $staffInfo->get("userid"), $groupInfo->get("chat_id"), $stat_time, $end_time, $groupInfo->toArray());
                }
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
     * @param $chat_id
     * @param $stat_time
     * @param $end_time
     * @param $groupInfo
     * Notes:
     * User: rand
     * Date: 2024/12/20 09:20
     * @return void
     */
    public static function statistic($corp, $rule, $staff_userid, $chat_id, $stat_time, $end_time, $groupInfo)
    {
        $memberListIndex = ArrayHelper::index($groupInfo["member_list"] ?? [], "userid");

        //当前统计会话存档员工信息
        $staffInfo = StaffModel::query()->where([
            "corp_id" => $corp->get("id"),
            "userid" => $staff_userid,
        ])->getOne();
        if (empty($staffInfo)) {
            return;
        }

        //员工不在群内，退出
        if (empty($memberListIndex[$staffInfo->get("userid")])) {
            return;
        }

        //员工入群时间
        $staffJoinGroupTime = $memberListIndex[$staffInfo->get("userid")]["join_time"] ?? time();

        //查询对应会话今日的聊天记录
        $msgListQuery = ChatMessageModel::query()->where([
            "corp_id" => $corp->get("id"),
            "roomid" => $chat_id,
        ]);

        $conversation_id = "";
        $staff_msg_no_day = 0;//员工消息数
        $staff_self_msg_num = 0;//员工自己发送的消息数，不区分工作时间
        $cst_msg_no_day = 0;//客户消息数
        $staff_msg_no_work = 0;//员工消息数(工作时间内)
        $cst_msg_no_work = 0;//客户消息数(工作时间内)
        $promoter_type = -1; //发起人类型 -1 无人 1 员工 2 客户
        $reply_status = -1; //回复状态 0 未回复，1:已回复
        $round_no = 0;//会话轮次数
        $round_flag = false; // 这个用来证明当前消息是一轮开始的 并且一定是一轮开始的第一条消息 用来计算 round_no
        $recover_in_time = 0;//指定时间内回复轮次
        $msg_reply_sec = $rule['msg_reply_sec'] ?? 3;
        $other_effect = $rule['other_effect'] ?? 0;
        $group_staff_type = $rule['group_staff_type'] ?? 2;
        $group_staff_ids = $rule['group_staff_ids'] ?? [];

        $at_num = 0;//at次数
        $at_round_no = 0;//at后会话轮次数
        $at_round_flag = false;//at后会话轮次数
        $at_recover_in_time = 0;//at后指定时间内回复轮次
        $at_msg_reply_sec = $rule['at_msg_reply_sec'] ?? 3;

        $base_at_msg_time = 0;


        $round_msg_time = 0;//每一轮消息的时间
        $last_msg_id = "";//最后一条消息

        $date_no = date("Ymd", $stat_time);
        $atUserName = $staffInfo->get("name");
        $is_new_room = $date_no == date("Ymd", $staffJoinGroupTime) ? 1 : 0;//是否为新加入群聊


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
                $conversation_id = $msgInfo["conversation_id"] ?? "";

                //验证消息是否在工作时间内
                $at_work_time = checkMsgInWorkTime($rule["work_time"] ?? [], $msgInfo["msg_time"]);
                $has_hit_keyword = self::checkCstMsgKeyword($msgInfo, $rule["cst_keywords"]["full"] ?? [], $rule["cst_keywords"]["half"] ?? [], $rule["cst_keywords"]["msg_type_filter"] ?? []);

                //当日会话消息发起人
                if ($promoter_type == -1) {
                    $promoter_type = $msgInfo["from_role"]->value;
                }


                if ($msgInfo["from_role"] == EnumChatMessageRole::Staff) {//员工
                    $staff_msg_no_day++;

                    if ($at_work_time) {//工作时间内消息
                        $staff_msg_no_work++;
                    }

                    ddump([$msgInfo["from"],$staff_userid]);
                    if ($msgInfo["from"] == $staff_userid) {//员工自己发送的消息
                        $staff_self_msg_num++;
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


                    // 客户工作时间发的 没有命中关键词的消息，继续验证是否at了当前员工
                    if (empty($has_hit_keyword)) {

//                    如果客户消息中at了当前员工
                        $target_to_name = $atUserName ? "@{$atUserName}" : '';
                        $msg_content = $msgInfo["raw_content"]["content"] ?? "";
                        if (!empty($target_to_name) && strpos($msg_content, $target_to_name) !== false) {
                            $at_num++;//at次数增加
                            $base_at_msg_time = strtotime($msgInfo["msg_time"]);

                            if (empty($at_round_flag)) {
                                $at_round_no++;//at轮次增加
                                $at_round_flag = true;//开启at轮次
                            }
                        }
                    }
                }

//                员工发送的，且，不是新轮次,标记为已回复，轮次刷新
                $staffReplay = false;//员工回复算已回复
                if ($msgInfo["from"] == $staff_userid) {
                    $staffReplay = true;
                } else if ($other_effect == 1 && $group_staff_type == 2) {//全部员工回复算已回复
                    $staffReplay = true;
                } else if ($other_effect == 1 && $group_staff_type == 1 && in_array($msgInfo["from"], $group_staff_ids)) {//指定员工回复算已回复
                    $staffReplay = true;
                }

                if (!empty($round_flag) && $msgInfo["from_role"] == EnumChatMessageRole::Staff && $staffReplay) {
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


//                    at后的，需要指定员工回复才更新轮次
                    if ($at_round_flag && $staffInfo->get("userid") == $msgInfo["from"]) {
                        $at_round_flag = false;

                        // at后的回复时间
                        $at_effect_time = strtotime($msgInfo['msg_time']) - $base_at_msg_time;

                        // 回复时长小于指定时长，
                        if ($at_effect_time < $at_msg_reply_sec * 60) {
                            $at_recover_in_time++;
                        }
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
            "room_id" => $chat_id,
        ];

        $updateData = [
            "staff_msg_no_day" => $staff_msg_no_day,
            "cst_msg_no_day" => $cst_msg_no_day,
            "staff_msg_no_work" => $staff_msg_no_work,
            "cst_msg_no_work" => $cst_msg_no_work,
            "round_no" => $round_no,
            "recover_in_time" => $recover_in_time,
            "at_num" => $at_num,
            "at_round_no" => $at_round_no,
            "at_recover_in_time" => $at_recover_in_time,
            "reply_status" => $reply_status,
            "promoter_type" => $promoter_type,
            "last_msg_id" => $last_msg_id,
            "is_new_room" => $is_new_room,
            "conversation_id" => $conversation_id,
            "staff_self_msg_num" => $staff_self_msg_num,
        ];

        DetailModel::updateOrCreate($where, array_merge($updateData, $where));
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
            if (str_contains($msg_content, $word)) {
                return true;
            }
        }
        return false;
    }
}
