<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Service;

use Common\DB\BaseModel;
use Common\Yii;
use Modules\Main\Enum\EnumChatConversationType;
use Modules\Main\Model\CorpModel;
use Throwable;
use Yiisoft\Arrays\ArrayHelper;

class GroupService
{
    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 群列表
     * User: rand
     * Date: 2024/11/14 17:02
     * @return array
     * @throws Throwable
     */
    public static function list(CorpModel $corp, $data): array
    {
        $page = $data["page"] ?? 1;
        $size = $data["size"] ?? 20;

        $toWhere = '';
        // 搜索关键字
        if (!empty($data["keyword"])) {
            $toWhere .= " and (c.name ilike '%{$data['keyword']}%' or c.chat_id = '{$data['keyword']}')";
        }

        // 群主筛选
        if (!empty($data["owner"])) {
            $toWhere .= " and c.owner = '{$data["owner"]}' ";
        }

        // 群筛选
        if (!empty($data["chat_id"])) {
            $toWhere .= " and c.chat_id = '{$data["chat_id"]}' ";
        }

        // 群创建时间
        if (!empty($data["start_time"]) && !empty($data["stop_time"])) {
            $toWhere .= " and c.created_at between '{$data['start_time']}' and '{$data['stop_time']}' ";
        }

        //有过会话记录的
        if (!empty($data['has_conversation'])) {
            $toWhere .= " and c.has_conversation=true ";
        }

        // 排序条件
        $order_by = "group_create_time";
        if (isset($data["order_by"])) {
            $order_by = $data["order_by"];
        }


        // 拼接基础sql
        $offset = ($page - 1) * $size;
        $type = EnumChatConversationType::Group->value;
        $baseSql = /** @lang sql */ <<<SQL
select v.id as conversations_id, v.last_msg_time, v.type,v.is_collect,v.collect_reason,v.collect_time, c.*
from main.groups as c
left join main.chat_conversations as v on c.chat_id= v."to" and v.corp_id = '{$corp->get('id')}'  and v.type = {$type}
where c.corp_id = '{$corp->get('id')}' {$toWhere} order by {$order_by} desc
SQL;
        $countSql = "select count(*) as total from({$baseSql})";
        $listSql = "{$baseSql} offset {$offset} limit {$size}";

        $totalRes = Yii::db()->createCommand($countSql)->queryColumn()[0];
        $listRes = Yii::db()->createCommand($listSql)->queryAll();

        $res = BaseModel::buildPaginate($page, $size, $totalRes, $listRes);



        if (!empty($res['items'])) {
            $staffListIndex = [];
            foreach ($res["items"] as &$group) {
                $group['admin_list'] = json_decode($group['admin_list'], true);
                $group['member_list'] = json_decode($group['member_list'], true);
                $staffList = array_filter($group['member_list'], function ($node) {
                    return $node["type"] == 1;
                });
                unset($group['member_list']);
                $staffListIndex = array_merge($staffListIndex, ArrayHelper::index($staffList, "userid"));
            }
            unset($group);

            // 群主名称
            foreach ($res['items'] as &$group) {
                $staff = $staffListIndex[$group['owner']];
                $group['owner_name'] = $staff['name'] ?? '';
            }
            unset($group);
        }

        // 上次同步时间
        $res['last_sync_time'] = date("Y-m-d H:i:s", strtotime($corp->get('sync_group_time')));


        return $res;
    }
}
