<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\HintKeywords\Consumer;


use Carbon\Carbon;
use Common\Yii;
use Google\Type\DateTime;
use Modules\HintKeywords\Library\DingDing\DingDingTalkService;
use Modules\HintKeywords\Library\DingDing\TextMsgService;
use Modules\HintKeywords\Model\DetailModel;
use Modules\HintKeywords\Model\HintKeywordsModel;
use Modules\HintKeywords\Model\NoticeConfig;
use Modules\HintKeywords\Model\RuleModel;
use Modules\Main\Enum\EnumChatConversationType;
use Modules\Main\Enum\EnumChatMessageRole;
use Modules\Main\Model\ChatMessageModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\CustomersModel;
use Modules\Main\Model\GroupModel;
use Modules\Main\Model\StaffModel;
use Yiisoft\Arrays\ArrayHelper;
use Zxing\QrReader;

/**
 * @author rand
 * @ClassName StatisticsHintConsumer
 * @date 2024/12/611:26
 * @description
 */
class StatisticsHintConsumer
{

    private readonly CorpModel $corp;
    private $hintKeywordsList;
    private $hint_type = 1;
    private $target_msg_type = "";
    private $target_msg_content = "";

    public function __construct($corp)
    {
        $this->corp = $corp;
    }

    public function handle()
    {

        //获取是否存在敏感词规则
        $ruleList = RuleModel::query()->where(["corp_id" => $this->corp->get("id"), "switch_status" => 1])->getAll()->toArray();

        if (empty($ruleList)) {
            return;
        }

        //获取规则内配置的敏感词组
        $hintKeywordIds = [];
        array_walk($ruleList, function ($item) use (&$hintKeywordIds) {
            $hintKeywordIds = array_merge($hintKeywordIds, $item["hint_group_ids"]);
        });

        $hintKeywordsInfoIndex = [];
        if (!empty($hintKeywordIds)) {
            $hintKeywordsInfo = HintKeywordsModel::query()->where(["corp_id" => $this->corp->get("id")])->andWhere(["in", "id", $hintKeywordIds])->getAll()->toArray();

            if (!empty($hintKeywordsInfo)) {
                $hintKeywordsInfoIndex = array_column($hintKeywordsInfo, "keywords", "id");
            }
        }

        $this->hintKeywordsList = $hintKeywordsInfoIndex;

        //查询消息列表
        $notice = NoticeConfig::query()->where(["corp_id" => $this->corp->get("id")])->getOne();
        if (!empty($notice)) {
            $noticeInfo = $notice->toArray();
        }

        if (empty($noticeInfo["statistics_msg_time"])) {
            $baseMsgTime = min(array_column($ruleList, "created_at"));
        } else {
            $baseMsgTime = $noticeInfo["statistics_msg_time"];
        }

        $statistics_msg_time = 0;
        //遍历消息列表
        $page = 1;
        $limit = 500;
        $msgQuery = ChatMessageModel::query()->where(["corp_id" => $this->corp->get("id")])->andWhere([">", "msg_time", $baseMsgTime])->orderBy("msg_time");
        while (true) {

            $offset = ($page - 1) * $limit;
            $msgList = $msgQuery->offset($offset)->limit($limit)->getAll()->toArray();

            if (empty($msgList)) {
                break;
            }
            $roomIds = array_column($msgList, "roomid");
            $groupInfoIndex = [];
            if (!empty($roomIds)) {
                $groupInfo = GroupModel::query()->select(["id", "chat_id"])->where(["corp_id" => $this->corp->get("id")])->andWhere(["in", "chat_id", array_values(array_unique($roomIds))])->getAll()->toArray();
                $groupInfoIndex = ArrayHelper::index($groupInfo, "chat_id");
            }
            //遍历验证消息
            foreach ($msgList as $msgInfo) {
                $statistics_msg_time = $msgInfo["msg_time"];

                //如果是群聊，且没有群信息，同步一下群
                if ($msgInfo["conversation_type"] == EnumChatConversationType::Group && !array_key_exists($msgInfo["roomid"] ?? "", $groupInfoIndex)) {
                    try {
                        GroupModel::syncOne($this->corp, $msgInfo["roomid"] ?? "");
                    } catch (\Exception $e) {//群同步报错，退出验证当前消息
                        continue;
                    }
                }

                foreach ($ruleList as $rule) {

                    $this->target_msg_type = "";
                    $this->target_msg_content = "";

                    try {
                        $checkRes = $this->checkMsg($msgInfo, $rule);

                        //如果命中敏感词了，终止规则的遍历，进行下一消息匹配
                        if ($checkRes) {

                            //验证成功，开始推送
                            if (!empty($noticeInfo["notice_switch"])) {
                                $this->notice($noticeInfo, $msgInfo);
                            }

                            break;
                        }

                    } catch (\Exception $exception) {
                        ddump($exception->getTraceAsString());
                    }
                }

            }

            $page++;
        }

        if (!empty($statistics_msg_time)) {
            //跑完了，更新一下消息时间
            NoticeConfig::query()->where(["corp_id" => $this->corp->get("id")])->update([
                "statistics_msg_time" => $statistics_msg_time
            ]);
        }

        $this->statisticsTotal();

        unset($this->hintKeywordsList);

    }

    /**
     * Notes: 统计
     * User: rand
     * Date: 2024/12/10 10:45
     * @return void
     * @throws \Throwable
     * @throws \Yiisoft\Db\Exception\Exception
     * @throws \Yiisoft\Db\Exception\InvalidConfigException
     */
    public function statisticsTotal()
    {

        $whereSql = " corp_id = '" . $this->corp->get("id") . "'  ";

        $dayTotalSql = /** @lang sql */
            <<<SQL
select
     count(msg_id) as total,hint_type,from_role,rule_id
 from hint_keywords.detail where {$whereSql} group by hint_type,from_role,rule_id;
SQL;

        $res = Yii::db()->createCommand($dayTotalSql)->queryAll();

        $dayTotalDataNode = [
            "statistic_staff_keywords" => 0,
            "statistic_staff_msg" => 0,
            "statistic_cst_keywords" => 0,
            "statistic_cst_msg" => 0,
            "statistic_total" => 0,
        ];
        $dayTotalData = [];

        foreach ($res as $node) {
            $rule_id = $node["rule_id"];
            if (!array_key_exists($rule_id, $dayTotalData)) {
                $dayTotalData[$rule_id] = $dayTotalDataNode;
            }
            if ($node["hint_type"] == 1) {//敏感词
                if ($node["from_role"] == EnumChatMessageRole::Customer->value) {
                    $dayTotalData[$rule_id]["statistic_cst_keywords"] += $node["total"] ?? 0;
                } else if ($node["from_role"] == EnumChatMessageRole::Staff->value) {
                    $dayTotalData[$rule_id]["statistic_staff_keywords"] += $node["total"] ?? 0;
                }
            } else {//敏感行为
                if ($node["from_role"] == EnumChatMessageRole::Customer->value) {
                    $dayTotalData[$rule_id]["statistic_cst_msg"] += $node["total"] ?? 0;
                } else if ($node["from_role"] == EnumChatMessageRole::Staff->value) {
                    $dayTotalData[$rule_id]["statistic_staff_msg"] += $node["total"] ?? 0;
                }
            }

            //总触发数
            $dayTotalData[$rule_id]["statistic_total"] += $node["total"] ?? 0;
        }

        foreach ($dayTotalData as $rule_id => $detail) {
            RuleModel::query()->where(["corp_id" => $this->corp->get("id"), "id" => $rule_id])->update($detail);
        }


        //统计昨日数据
        $startDate = date("Y-m-d", strtotime("-1 day")) . " 00:00:00";
        $endDate = date("Y-m-d", strtotime("-1 day")) . " 23:59:59";

        $whereSql = " corp_id = '" . $this->corp->get("id") . "' and msg_time >= '{$startDate}' and msg_time < '{$endDate}' ";
        $yesterdayTotalSql = /** @lang sql */
            <<<SQL
select
     count(msg_id) as total,rule_id
 from hint_keywords.detail where {$whereSql} group by rule_id;
SQL;

        $yesterdayRes = Yii::db()->createCommand($yesterdayTotalSql)->queryAll();
        foreach ($yesterdayRes as $data) {
            RuleModel::query()->where(["corp_id" => $this->corp->get("id"), "id" => $data["rule_id"]])->update([
                "statistic_yesterday" => $data["total"] ?? 0
            ]);
        }


        //统计今日数据
        $startDate = date("Y-m-d", time()) . " 00:00:00";

        $whereSql = " corp_id = '" . $this->corp->get("id") . "' and msg_time >= '{$startDate}' ";
        $todayTotalSql = /** @lang sql */
            <<<SQL
select
     count(msg_id) as total,rule_id
 from hint_keywords.detail where {$whereSql} group by rule_id;
SQL;

        $todayRes = Yii::db()->createCommand($todayTotalSql)->queryAll();
        foreach ($todayRes as $data) {
            RuleModel::query()->where(["corp_id" => $this->corp->get("id"), "id" => $data["rule_id"]])->update([
                "statistic_today" => $data["total"] ?? 0
            ]);
        }


    }


    /**
     * @param  $msg
     * @param  $rule
     * Notes: 验证消息是否匹配到敏感词规则
     * User: rand
     * Date: 2024/12/6 15:00
     * @return bool
     */
    public function checkMsg($msg, $rule): bool
    {
        //如果会话类型不是全部消息类型，且消息会话类型不匹配，推出
        if ($rule["chat_type"] != 0 && $rule["chat_type"] != $msg["conversation_type"]->value) {
            return false;
        }

        //如果指定会话类型是指定群聊
        if ($rule["chat_type"] == EnumChatConversationType::Group->value && $rule["group_chat_type"] == 2 && !in_array($msg["roomid"] ?? "", $rule["group_chat_id"])) {
            return false;
        }

        //如果消息发送人是指定角色
        if ($rule["check_user_type"] != 0 && $rule["check_user_type"] != $msg["from_role"]->value) {
            return false;
        }


        //开始验证内容

        $insertData = [
            "corp_id" => $this->corp->get("id"),
            "from_user_id" => $msg["from"],
            "from_role" => $msg["from_role"]->value,
            "to_role" => $msg["to_role"]->value,
            "to_user_id" => $msg["to_list"][0] ?? "",
            "msg_type" => $msg["msg_type"],
            "hint_type" => 1,
            "target_msg_type" => "text",
            "msg_id" => $msg["msg_id"],
            "msg_time" => $msg["msg_time"],
            "msg_content" => $msg["msg_content"],
            "conversation_type" => $msg["conversation_type"]->value,
            "conversation_id" => $msg["conversation_id"],
            "rule_id" => $rule["id"]
        ];

        //群聊会话
        if ($msg["conversation_type"] == EnumChatConversationType::Group) {
            $insertData["to_user_id"] = $msg["roomid"] ?? "";
        }

        $is_checked = 0;
        //验证敏感词是否匹配到了
        if ($msg["msg_type"] === "text") {//文本类型的，需要匹配敏感词

            $ruleKeywords = $rule["hint_keywords"];

            foreach ($rule["hint_group_ids"] as $hint_group_id) {
                $ruleKeywords = array_merge($ruleKeywords, $this->hintKeywordsList[$hint_group_id] ?? []);
            }

            //遍历敏感词列表
            foreach ($ruleKeywords as $ruleKeyword) {
                if (strpos($msg["msg_content"], $ruleKeyword) !== false) {

                    //敏感词类型的，验证一下同一消息规则是否已存在，避免有会话存档的群消息当作推送消息，导致重复数据多次写入
                    $hisData = DetailModel::query()->where(["corp_id" => $this->corp->get("id"), "msg_id" => $insertData["msg_id"], "rule_id" => $insertData["rule_id"]])->getOne();
                    if (!empty($hisData)) {
                        return false;
                    }

                    $is_checked = 1;
                    $this->hint_type = 1;
                    $insertData["hint_type"] = $this->hint_type;
                    $insertData["target_msg_type"] = "text";
                    $insertData["hint_keyword"] = $ruleKeyword;
                    DetailModel::create($insertData);//写库
                    break;//匹配到了，终止遍历
                }
            }

            //包含链接的文本，验证一下
            if (in_array("link_text", $rule["target_msg_type"]) && $this->containsLink($msg["msg_content"])) {
                $is_checked = 1;
                $this->hint_type = 2;
                $this->target_msg_type = "link_text";
                $insertData["hint_type"] = $this->hint_type;//敏感行为
                $insertData["target_msg_type"] = "link_text";
                DetailModel::create($insertData);//写库
            }

        } else if ($msg["msg_type"] === "image" && in_array("qr_code", $rule["target_msg_type"])) {//图片类型，验证一下是否存在二维码

            //验证是否为二维码
            $isQrcode = checkImgIsQrcode($_ENV['MINIO_ENDPOINT'] . $msg["msg_content"]);

            if ($isQrcode["is_qrcode"]) {
                $is_checked = 1;
                $this->hint_type = 2;
                $this->target_msg_type = "qr_code";
                $this->target_msg_content = $isQrcode["qrcode_content"] ?? "";
                $insertData["hint_type"] = $this->hint_type;//敏感行为
                $insertData["target_msg_type"] = "qr_code";
                $insertData["msg_content"] = $isQrcode["qrcode_content"] ?? "";
                DetailModel::create($insertData);//写库
            }

        } else {
            if (in_array($msg["msg_type"], $rule["target_msg_type"])) {//命中敏感行为
                $is_checked = 1;
                $this->hint_type = 2;
                $insertData["hint_type"] = $this->hint_type;//敏感行为
                $insertData["target_msg_type"] = $msg["msg_type"];

                //链接，把内容提取出来
                if ($msg["msg_type"] == "link") {
                    $insertData["msg_content"] = $msg["raw_content"]["link_url"] ?? "";
                }
                //小程序
                if ($msg["msg_type"] == "weapp") {
                    $insertData["msg_content"] = $msg["raw_content"]["displayname"] ?? "";
                }

                DetailModel::create($insertData);//写库
            }
        }

        return $is_checked;
    }


    /**
     * @param $string
     * Notes: 验证是否存在链接
     * User: rand
     * Date: 2024/12/6 17:20
     * @return bool
     */
    function containsLink($string): bool
    {
        $pattern = '/https?:\/\/[^\s]+/';
        return preg_match($pattern, $string) > 0;
    }


    /**
     * @param $noticeInfo
     * @param $msgInfo
     * Notes: 推送消息通知
     * User: rand
     * Date: 2024/12/6 17:55
     * @return void
     */
    public function notice($noticeInfo, $msgInfo)
    {
        $send_msg = "";

        if ($msgInfo["from_role"] == EnumChatMessageRole::Staff) {
            $staffInfo = StaffModel::query()->where(["corp_id" => $this->corp->get("id"), "userid" => $msgInfo["from"]])->getOne();
            if (!empty($staffInfo)) {
                $send_msg = "员工【" . ($staffInfo->get("name")) . "】";
            }
        } else if ($msgInfo["from_role"] == EnumChatMessageRole::Customer) {
            $cstInfo = CustomersModel::query()->where(["corp_id" => $this->corp->get("id"), "external_userid" => $msgInfo["from"]])->getOne();
            if (!empty($cstInfo)) {
                $send_msg = "客户【" . ($cstInfo->get("external_name")) . "】";
            }
        } else {
            $send_msg = "群成员";
        }

        $hintTypeTitle = "敏感词";
        if ($this->hint_type == 2) {
            $hintTypeTitle = "";
            switch ($msgInfo["msg_type"]) {
                case "link":
                    $hintTypeTitle = "链接";
                    break;
                case "weapp":
                    $hintTypeTitle = "小程序";
                    break;
                case "card":
                    $hintTypeTitle = "名片";
                    break;
                case "image":
                    if ($this->target_msg_type == "qr_code" && !empty($this->target_msg_content)) {
                        $hintTypeTitle = "含二维码图片";
                    }
                    break;
                case "text":
                    if ($this->target_msg_type == "link_text") {
                        $hintTypeTitle = "含链接文本";
                    }
            }
        }

        if ($msgInfo["conversation_type"] == EnumChatConversationType::Group) {//群聊
            $groupInfo = GroupModel::query()->where(["corp_id" => $this->corp->get("id"), "chat_id" => $msgInfo["roomid"]])->getOne();

            //没有发送人信息，取一下群成员
            if (empty($send_msg)) {
                $memberList = ArrayHelper::index($groupInfo->toArray()["member_list"]??[],"userid");

                if (array_key_exists($msgInfo["from"],$memberList)) {
                    $send_msg = "群成员【" . ($memberList[$msgInfo["from"] ?? ""]["name"] ?? "未知昵称") . "】";
                } else {
                    $send_msg = "群成员";
                }
            }

            if (!empty($groupInfo)) {
                $send_msg .= "在群聊【" . $groupInfo->get("name") . "】中发送了" . $hintTypeTitle;
            }

            $staffInfo = StaffModel::query()->where(["corp_id" => $this->corp->get("id"), "userid" => $groupInfo->get("owner")])->getOne();

            if (!empty($staffInfo)) {
                $send_msg .= "\r\n";
                $send_msg .= "群主：" . $staffInfo->get("name");
            }
        } else if ($msgInfo["conversation_type"] == EnumChatConversationType::Single) {
            //单聊的，还要查一下消息接收人
            if ($msgInfo["to_role"] == EnumChatMessageRole::Staff) {
                $staffInfo = StaffModel::query()->where(["corp_id" => $this->corp->get("id"), "userid" => $msgInfo["to_list"][0] ?? "_"])->getOne();
                if (!empty($staffInfo)) {
                    $send_msg .= "与员工【" . ($staffInfo->get("name")) . "】";
                }
            } else if ($msgInfo["to_role"] == EnumChatMessageRole::Customer) {
                $cstInfo = CustomersModel::query()->where(["corp_id" => $this->corp->get("id"), "external_userid" => $msgInfo["to_list"][0] ?? "_"])->getOne();
                if (!empty($cstInfo)) {
                    $send_msg .= "与客户【" . ($cstInfo->get("external_name")) . "】";
                }
            }
            $send_msg .= "聊天中发送了" . $hintTypeTitle;
        } else if ($msgInfo["conversation_type"] == EnumChatConversationType::Internal) {//同事会话
            $staffInfo = StaffModel::query()->where(["corp_id" => $this->corp->get("id"), "userid" => $msgInfo["to_list"][0] ?? "_"])->getOne();
            if (!empty($staffInfo)) {
                $send_msg .= "与员工【" . ($staffInfo->get("name")) . "】";
            }
            $send_msg .= "聊天中发送了" . $hintTypeTitle;
        }


        //发送内容
        $send_msg .= "\r\n";

        if ($this->target_msg_type == "qr_code" && !empty($this->target_msg_content)) {
            $send_msg .= "二维码内容：" . $this->target_msg_content;
        } else if (in_array($msgInfo["msg_type"], ["link", "weapp", "card"])) {
            switch ($msgInfo["msg_type"]) {
                case "link":
                    $send_msg .= "链接地址：" . $msgInfo["raw_content"]["link_url"] ?? "";
                    break;
                case "weapp":
                    $send_msg .= "发送内容：" . $msgInfo["raw_content"]["displayname"] ?? "小程序";
                    break;
                case "card":
                    $send_msg .= "发送内容：名片";
                    break;
            }
        } else {
            $send_msg .= "发送内容：" . $msgInfo["msg_content"] ?? "";
        }


        //触发时间
        $send_msg .= "\r\n";
        $send_msg .= "触发时间：" . date("Y-m-d H:i:s", strtotime($msgInfo["msg_time"] ?? date("Y-m-d H:i:s", time())));

        //自建应用通知
        if ($noticeInfo["app_notice_switch"]) {
            foreach ($noticeInfo["app_notice_userid"] as $item) {
                $this->noticeApp($item, $send_msg);
            }
        }

        //企业微信群通知
        if ($noticeInfo["wechat_notice_switch"] && !empty($noticeInfo["wechat_notice_hook"])) {
            $this->noticeWechat($noticeInfo, $send_msg);
        }

        //钉钉群通知
        if ($noticeInfo["dingtalk_notice_switch"] && !empty($noticeInfo["dingtalk_notice_hook"])) {
            $this->noticeDingTalk($noticeInfo, $send_msg);
        }


    }

    /**
     * @param $toUser
     * @param $msg
     * Notes: 推送应用消息
     * User: rand
     * Date: 2024/12/6 18:45
     * @return void
     * @throws \Throwable
     */
    function noticeApp($toUser, $msg)
    {
        $data = [
            "touser" => $toUser,
            "msgtype" => "text",
            "text" => [
                "content" => $msg,
            ],
            "agentid" => $this->corp->get("agent_id")
        ];
        $this->corp->postWechatApi("https://qyapi.weixin.qq.com/cgi-bin/message/send", $data, "json");
    }

    /**
     * @param $config
     * @param $msg
     * Notes: 企微群消息推送
     * User: rand
     * Date: 2024/12/6 18:17
     * @return void
     * @throws \Throwable
     */
    function noticeWechat($config, $msg)
    {

        $text = [
            "content" => $msg,
        ];

        if ($config["wechat_notice_type"] == 1) {
            $text["mentioned_list"] = ["@all"];
        } else if ($config["wechat_notice_type"] == 2) {
            $text["mentioned_list"] = $config["wechat_notice_user"] ?? [];
        }

        $data = [
            "msgtype" => "text",
            "text" => $text
        ];

        $this->corp->postWechatApi($config["wechat_notice_hook"] ?? "", $data, "json");

    }

    /**
     * @param $config
     * @param $msg
     * Notes: 钉钉群推送
     * User: rand
     * Date: 2024/12/6 18:00
     * @return void
     */
    function noticeDingTalk($config, $msg)
    {
        $text_msg = new TextMsgService();
        if ($config['dingtalk_notice_type'] == 1) {
            $text_msg->setIsAtAll(true);
        } elseif ($config['dingtalk_notice_type'] == 2) {
            if (!empty($config['dingtalk_notice_user'])) {
                $text_msg->setAtMobiles($config['dingtalk_notice_user']);
            }
        }
        parse_str(parse_url($config['dingtalk_notice_hook'], PHP_URL_QUERY), $query);

        $ding = new DingDingTalkService($query['access_token'], $config['dingtalk_notice_secret'], $text_msg);
        $ding->sendMsg($msg);
    }


}
