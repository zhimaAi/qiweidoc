<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Consumer;

use Modules\Main\Enum\EnumChatConversationType;
use Modules\Main\Model\ChatConversationsModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\GroupModel;
use Throwable;

/**
 * Notes: 群同步
 * User: rand
 * Date: 2024/11/6 16:57
 */
class SyncGroupConsumer
{
    private readonly CorpModel $corp;

    public function __construct(CorpModel $corp)
    {
        $this->corp = $corp;
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $cursor = "";
        $limit = 1000;

        $data = [
            "limit" => $limit,
        ];

        //所有群状态变更为更新中
        GroupModel::query()->where(['corp_id' => $this->corp->get('id')])->update(['group_status' => 10]);

        while (true) {
            if (!empty($cursor)) {
                $data["cursor"] = $cursor;
            }
            $res = $this->corp->postWechatApi("/cgi-bin/externalcontact/groupchat/list", $data, "json");

            if (empty($res["group_chat_list"])) {
                break;
            }

            // 遍历群
            $requestData = [];
            $groupStatusIndex = [];
            foreach ($res["group_chat_list"] as $group) {
                $detailData = [
                    "chat_id" => $group["chat_id"] ?? "",
                    "need_name" => 1,
                ];
                $groupStatusIndex[$group["chat_id"]] = $group["status"] ?? 0;
                $requestData[] = [
                    "url" => "/cgi-bin/externalcontact/groupchat/get",
                    "method" => "POST",
                    "body" => $detailData,
                ];
            }

            $batchRes = $this->corp->batchPostWechatApi([
                "requests" => $requestData,
                "concurrency" => 50,
                "qps" => 50,
                "timeout" => 120,
            ]);

            if (empty($batchRes["responses"])) {
                continue;
            }

            $successGroupId = [];
            foreach ($batchRes["responses"] as $response) {
                if ($response["status_code"] != 200) {
                    continue;
                }

                $respBody = json_decode($response["body"], true);
                if ($respBody["errcode"] != 0 || empty($respBody["group_chat"])) {
                    continue;
                }

                $groupInfo = $respBody["group_chat"];
                if (empty($groupInfo['chat_id'])) {
                    continue;
                }

                $staffUserNum = count(array_filter($groupInfo["member_list"], function ($item) {
                    return $item["type"] == 1;
                }));
                $cstUserNum = count(array_filter($groupInfo["member_list"], function ($item) {
                    return $item["type"] == 2;
                }));
                $successGroupId[] = $groupInfo["chat_id"];

                //没有群名的，群成员昵称提取一下
                if (empty($groupInfo["name"])) {
                    $staffUserName = [];
                    foreach ($groupInfo["member_list"] as $item) {
                        if (count($staffUserName) > 3) {
                            break;
                        }
                        if (!empty($item["name"])) {
                            $staffUserName[] = $item["name"];
                        }
                    }
                    $groupInfo["name"] = implode(",", $staffUserName);
                }

                GroupModel::updateOrCreate(['and',
                    ["chat_id" => $groupInfo["chat_id"]],
                    ["corp_id" => $this->corp->get('id')],
                ], [
                    "chat_id" => $groupInfo["chat_id"],
                    "corp_id" => $this->corp->get('id'),
                    "name" => $groupInfo["name"] ?? "",
                    "group_create_time" => date('Y-m-d H:i:s', $groupInfo["create_time"] ?? 0),
                    "member_list" => $groupInfo["member_list"] ?? [],
                    "owner" => $groupInfo["owner"] ?? "",
                    "group_status" => $groupStatusIndex[$groupInfo["chat_id"]] ?? 0,
                    "member_version" => $groupInfo["member_version"] ?? "",
                    "admin_list" => $groupInfo["admin_list"] ?? [],
                    "staff_num" => $staffUserNum,
                    "cst_num" => $cstUserNum,
                    "total_member" => $staffUserNum + $cstUserNum,
                ]);

                // 从会话数据中查找该群有没有会话记录
                $conversation = ChatConversationsModel::query()->where(['and',
                    ['corp_id' => $this->corp->get('id')],
                    ['type' => EnumChatConversationType::Group->value],
                    ['to' => $groupInfo["chat_id"]],
                ])->getOne();
                if (!empty($conversation)) {
                    GroupModel::hasConversationSave($this->corp, $groupInfo["chat_id"]);
                }
            }

            if (empty($res["next_cursor"])) {
                break;
            } else {
                $cursor = $res["next_cursor"];
            }
        }


        //更新中的数据删掉
        GroupModel::query()
            ->where(['and',
                ['corp_id' => $this->corp->get('id')],
                ['group_status' => 10],
            ])
            ->deleteAll();

        //同步完了，更新一下上次同步时间
        $this->corp->update(['sync_group_time' => now()]);
    }
}
