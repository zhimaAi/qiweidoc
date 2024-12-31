<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Model;

use Common\DB\BaseModel;
use Exception;
use LogicException;
use Throwable;

class GroupModel extends BaseModel
{
    public function getTableName(): string
    {
        return "main.groups";
    }

    protected function casts(): array
    {
        return [
            "id" => 'int',
            "created_at" => 'string',
            "updated_at" => 'string',
            "corp_id" => 'string',
            "chat_id" => 'string',
            "name" => 'string',
            "owner" => 'string',
            "member_version" => 'string',
            "group_status" => 'int',
            "group_create_time" => 'string',
            "staff_num" => 'int',
            "cst_num" => 'int',
            "total_member" => 'int',
            "member_list" => 'array',
            "admin_list" => 'array',
            "has_conversation" => 'boolean',
        ];
    }

    /**
     * @throws Throwable
     */
    public static function hasConversationSave(CorpModel $corp, string $chatId): void
    {
        $group = self::query()
            ->where(['and',
                ['corp_id' => $corp->get('id')],
                ['chat_id' => $chatId],
            ])
            ->getOne();
        if (!empty($group)) {
            $group->update(['has_conversation' => true]);
        }
    }

    /**
     * 从企微同步单条数据
     *
     * @throws Throwable
     */
    public static function syncOne(CorpModel $corp, string $chatID): void
    {
        $res = $corp->postWechatApi('/cgi-bin/externalcontact/groupchat/get', ['chat_id' => $chatID], 'json');
        if (empty($res['group_chat'])) {
            throw new LogicException('请求企微获取客户群详情数据失败');
        }

        $staffUserNum = count(array_filter($res['group_chat']["member_list"], function ($item) {
            return $item["type"] == 1;
        }));
        $cstUserNum = count(array_filter($res['group_chat']["member_list"], function ($item) {
            return $item["type"] == 2;
        }));
        GroupModel::updateOrCreate(['and',
            ["corp_id" => $corp->get('id')],
            ["chat_id" => $chatID],
        ], [
            "corp_id" => $corp->get('id'),
            "chat_id" => $chatID,
            "name" => $res['group_chat']["name"] ?? "",
            "group_create_time" => date('Y-m-d H:i:s', $res['group_chat']["create_time"] ?? 0),
            "member_list" => $res['group_chat']["member_list"] ?? [],
            "owner" => $res['group_chat']["owner"] ?? "",
            "group_status" => $res['group_chat']["chat_id"] ?? 0,
            "member_version" => $res['group_chat']["member_version"] ?? "",
            "admin_list" => $res['group_chat']["admin_list"] ?? [],
            "staff_num" => $staffUserNum,
            "cst_num" => $cstUserNum,
            "total_member" => $staffUserNum + $cstUserNum,
        ]);
    }
}
