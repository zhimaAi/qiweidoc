<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Model;

use Common\DB\BaseModel;
use Exception;
use LogicException;
use Throwable;

class CustomersModel extends BaseModel
{
    public function getTableName(): string
    {
        return "main.customers";
    }

    protected function casts(): array
    {
        return [
            "id" => 'int',
            "created_at" => 'string',
            "updated_at" => 'string',
            "corp_id" => 'string',
            "staff_userid" => 'string',
            "staff_remark" => 'string',
            "staff_description" => 'string',
            "staff_tag_id_list" => 'array',
            "staff_remark_mobiles" => 'array',
            "add_way" => 'int',
            "oper_userid" => 'string',
            "external_userid" => 'string',
            "external_name" => 'string',
            "avatar" => 'string',
            "corp_name" => 'string',
            "corp_full_name" => 'string',
            "external_type" => 'int',
            "gender" => 'int',
            "add_time" => 'string',
            "external_profile" => 'array',
            "add_status" => 'int',
            "has_conversation" => 'boolean',
        ];
    }

    /**
     * @throws Throwable
     */
    public static function hasConversationSave(CorpModel $corp, string $externalUserid)
    {
        self::query()->where(['and',
            ['corp_id' => $corp->get('id')],
            ['external_userid' => $externalUserid],
        ])->update(['has_conversation' => true]);
    }

    /**
     * 从企微同步单条客户数据
     * @throws Throwable
     */
    public static function syncOne(CorpModel $corp, string $userID, string $externalUserID)
    {
        $res = $corp->getWechatApi('/cgi-bin/externalcontact/get', ['external_userid' => $externalUserID]);
        $externalContact = $res['external_contact'] ?? [];
        $followUsers = $res['follow_user'] ?? [];
        if (empty($externalContact) || empty($followUsers)) {
            throw new LogicException('请求企微获取客户详情数据失败');
        }

        $followUser = [];
        foreach ($followUsers as $user) {
            if ($user['userid'] == $userID) {
                $followUser = $user;

                break;
            }
        }
        if (empty($followUser)) {
            throw new LogicException('未找到添加此外部联系人的企业成员');
        }

        CustomersModel::updateOrCreate(['and',
            ['corp_id' => $corp->get('id')],
            ['staff_userid' => $userID],
            ['external_userid' => $externalUserID],
        ], [
            'corp_id' => $corp->get('id'),
            'staff_userid' => $userID,
            "staff_remark" => $followUser["remark"] ?? "",
            "staff_description" => $followUser["description"] ?? "",
            "add_time" => date('Y-m-d H:i:s', $followUser["createtime"] ?? 0),
            "staff_tag_id_list" => array_column($followUser['tags'], 'tag_id'),
            "staff_remark_mobiles" => $followUser["remark_mobiles"] ?? [],
            "add_way" => $followUser["add_way"] ?? 0,
            "oper_userid" => $followUser["oper_userid"] ?? "",
            "external_userid" => $externalContact["external_userid"] ?? "",
            "external_name" => $externalContact["name"] ?? "",
            "external_type" => $externalContact["type"] ?? 1,
            "external_profile" => $externalContact["external_profile"] ?? [],
            "avatar" => $externalContact["avatar"] ?? "",
            "corp_name" => $externalContact["corp_name"] ?? "",
            "corp_full_name" => $externalContact["corp_full_name"] ?? "",
            "gender" => $externalContact["gender"] ?? 0,
            "add_status" => 2,
        ]);
    }
}
