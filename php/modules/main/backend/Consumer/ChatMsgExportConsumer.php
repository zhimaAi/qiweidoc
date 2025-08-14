<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Consumer;

use Modules\Main\Enum\EnumChatConversationType;
use Modules\Main\Enum\EnumChatMessageRole;
use Modules\Main\Enum\EnumMessageType;
use Modules\Main\Model\ChatMessageModel;
use Modules\Main\Model\ChatMsgExportTaskModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\GroupModel;
use Modules\Main\Model\StorageModel;
use Modules\Main\Service\AuthService;
use Modules\Main\Service\ChatSessionService;
use Modules\Main\Service\CsvService;
use Modules\Main\Service\StorageService;
use Ramsey\Uuid\Uuid;
use Yiisoft\Arrays\ArrayHelper;

/**
 * Notes: 会话导出
 * User: rand
 * Date: 2025/8/8 15:46
 */
class ChatMsgExportConsumer
{

    private readonly CorpModel $corp;
    private readonly int $taskId;


    public function __construct($corp, int $taskId)
    {
        $this->corp = $corp;
        $this->taskId = $taskId;
    }

    public function handle()
    {

        if (empty($this->taskId) || empty($this->corp)) {
            return;
        }

        $task_info = self::getTaskInfo();

        if (empty($task_info) || $task_info["state"] == ChatMsgExportTaskModel::STATE_CANCEL) {//已取消
            return;
        }

        //更新为导出中
        ChatMsgExportTaskModel::query()->where(["id" => $this->taskId, "corp_id" => $this->corp->get("id")])->update([
            "state" => ChatMsgExportTaskModel::STATE_EXPORTING
        ]);

        $conversationId = $task_info["req_data"]["conversation_id"] ?? "";
        $groupChatId = $task_info["req_data"]["group_chat_id"] ?? "";
        $msg_start_time = $task_info["req_data"]["msg_start_time"] ?? 0;
        $msg_end_time = $task_info["req_data"]["msg_end_time"] ?? 0;

        $conversation_info = ChatSessionService::getConversationInfo($conversationId, $groupChatId);

        $group_member = [];
        //如果是群聊的会话,单独查一下群信息和群成员信息
        if ($conversation_info["conversations_info"]["type"] == EnumChatConversationType::Group) {
            $group_info = GroupModel::query()->select(["name", "member_list"])->where(["chat_id" => $groupChatId])->getOne()->toArray();
            if (empty($group_info)) {
                return;
            }
            $group_member = ArrayHelper::index($group_info["member_list"], "userid");
        }

        $page = 1;
        $size = 100;

        try {
            $filePath = '/tmp/';
            $fileName = $conversation_info["task_title"] . "_" . time() . ".csv";
            $csv = new CsvService();
            $csv->writeCsv([
                "会话类型",
                "会话名称",
                "发送人",
                "发送人身份",
                "发送内容",
                "消息类型",
                "发送时间",
            ], $filePath, $fileName);


            $all_msg = [];
            $revoke_msg_id = [];
            while (true) {
                $task_info = self::getTaskInfo();
                if (empty($task_info) || $task_info["state"] == ChatMsgExportTaskModel::STATE_CANCEL) {//已取消
                    return;
                }

                $msg_list = self::getMsgList($this->corp->get("id"), $conversationId, $page, $size, $msg_start_time, $msg_end_time);
                if ($msg_list['items']->isEmpty()) {
                    break;
                }
                foreach ($msg_list["items"] as $item) {
                    $node = $item->toArray();;

                    //已撤回的消息不展示
                    if ($node["msg_type"] == EnumMessageType::Revoke->value) {
                        $revoke_msg_id[] = $node["raw_content"]["pre_msgid"];
                        continue;
                    }
                    $all_msg[] = $node;
                }
                $page++;
            }

            //写入数据
            foreach ($all_msg as $itemArr) {
                //标记撤回的消息
                if (in_array($itemArr["msg_id"], $revoke_msg_id)) {
                    $itemArr["msg_type"] = EnumMessageType::Revoke->value;
                }
                $msgInfo = [
                    $conversation_info["session_type"] ?? "",
                    $conversation_info["task_title"] ?? "",
                ];

                $from_user_name = "";//发送人信息

                if ($itemArr["from_role"] == EnumChatMessageRole::Staff) {//员工发送
                    $from_user_name = $conversation_info["user_info"][$itemArr["from"]]["name"] ?? "";
                } else if ($itemArr["from_role"] == EnumChatMessageRole::Customer) {//客户发送的
                    $cstInfo = $conversation_info["user_info"][$itemArr["from"]] ?? [];
                    $from_user_name = $cstInfo["external_name"] ?? "";

                    if (!empty($cstInfo["corp_name"])) {
                        $from_user_name .= "@" . $cstInfo["corp_name"] ?? "";
                    }
                }
                //如果没有发送人信息，群聊，从群成员里面找一找
                if (empty($from_user_name) && $conversation_info["conversations_info"]["type"] == EnumChatConversationType::Group) {
                    $from_user_name = $group_member[$itemArr["from"]]["name"] ?? "";
                }

                $msgInfo[] = $from_user_name;


                //发送人身份
                if ($itemArr["from_role"] == EnumChatMessageRole::Staff) {
                    $msgInfo[] = "员工";
                } else {
                    $msgInfo[] = "客户";
                }

                //发送内容
                $msg_content = "";
                switch ($itemArr["msg_type"]) {
                    case EnumMessageType::Link->value://链接
                        $msg_content = $itemArr["raw_content"]["link_url"] ?? "";
                        break;
                    case EnumMessageType::WeApp->value://小程序
                        $msg_content = $itemArr["raw_content"]["title"] ?? "";
                        break;
                    case EnumMessageType::File->value://文件
                    case EnumMessageType::Image->value://图片
                    case EnumMessageType::Video->value://视频
                    case EnumMessageType::Voice->value://语音
                    case EnumMessageType::Emotion->value://表情包
                    case EnumMessageType::MeetingVoiceCall->value://会议
                        $storage_info = StorageModel::query()->where(["hash" => $itemArr["msg_content"] ?? ""])->getOne()->toArray();
                        $msg_content = AuthService::getLoginDomain() . "/storage/session/" . ($storage_info["local_storage_object_key"] ?? "");
                        break;
                    default:
                        $msg_content = $itemArr["msg_content"] ?? "";
                        break;
                }
                $msgInfo[] = $msg_content;

                //消息类型
                $msgInfo[] = EnumMessageType::from($itemArr["msg_type"] ?? "text")->getLabel();

                $msgInfo[] = date("Y-m-d H:i:s", strtotime($itemArr["msg_time"]));

                //写入表格
                $csv->writeCsv($msgInfo, $filePath, $fileName);
            }

            $task_info = self::getTaskInfo();
            if (empty($task_info) || $task_info["state"] == ChatMsgExportTaskModel::STATE_CANCEL) {//已取消
                return;
            }

            //保存csv文件
            $csv->saveCsv();

            //存储文件到本地oss
            $storage = StorageService::saveLocal($filePath . $fileName);

            //导出完成
            ChatMsgExportTaskModel::query()->where(["id" => $this->taskId, "corp_id" => $this->corp->get("id")])->update([
                "state" => ChatMsgExportTaskModel::STATE_EXPORTED,
                "file_path" => $storage->get("hash")
            ]);

            //删除文件
            unlink($filePath . $fileName);

        } catch (\Exception $e) {
            //导出失败
            ChatMsgExportTaskModel::query()->where(["id" => $this->taskId, "corp_id" => $this->corp->get("id")])->update([
                "state" => ChatMsgExportTaskModel::STATE_ERROR,
                "error_info" => [
                    "line" => $e->getLine(),
                    "file" => $e->getFile(),
                    "msg" => $e->getMessage(),
                    "trace" => $e->getTraceAsString(),
                ]
            ]);
        }

    }

    /**
     * @param $corp_id
     * @param $conversation_id
     * @param $page
     * @param $size
     * @param $msg_start_time
     * @param $msg_end_time
     * Notes: 获取消息列表
     * User: rand
     * Date: 2025/8/11 15:34
     * @return array
     * @throws \Throwable
     */
    public static function getMsgList($corp_id, $conversation_id, $page = 1, $size = 500, $msg_start_time = 0, $msg_end_time = 0)
    {
        if (empty($msg_start_time) && empty($msg_end_time)) {
            $msg_start_time = date("Y-m-d H:i:s", strtotime("-3 month"));
            $msg_end_time = date("Y-m-d H:i:s", time());
        }
        $query = ChatMessageModel::query()
            ->where(['and',
                ['corp_id' => $corp_id],
                ['conversation_id' => $conversation_id],
            ])->andWhere(["between", "msg_time", $msg_start_time, $msg_end_time])->orderBy(['msg_time' => SORT_DESC]);

        return $query->paginate($page, $size);

    }

    /**
     * Notes: 获取任务信息
     * User: rand
     * Date: 2025/8/11 18:29
     * @return array
     * @throws \Throwable
     */
    public function getTaskInfo()
    {
        return ChatMsgExportTaskModel::query()->where(["id" => $this->taskId, "corp_id" => $this->corp->get("id")])->getOne()->toArray();
    }


}
