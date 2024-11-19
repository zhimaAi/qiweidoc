<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Services;

use App\Models\CorpModel;
use App\Models\GroupModel;
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
        $query = GroupModel::query()->where(['corp_id' => $corp->get('id')]);

        // 搜索关键字
        if (! empty($data["keyword"])) {
            $query->andWhere(['or',
                ['ilike', 'name', $data['keyword']],
                ['chat_id' => $data['keyword']],
            ]);
        }

        // 群主筛选
        if (! empty($data["owner"])) {
            $query->andWhere(['owner' => $data['owner']]);
        }

        // 群筛选
        if (! empty($data["chat_id"])) {
            $query->andWhere(['chat_id' => $data['chat_id']]);
        }

        // 群创建时间
        if (! empty($data["start_time"]) && ! empty($data["stop_time"])) {
            $query->andWhere(['between',
                date('Y-m-d H:i:s', $data['start_time']),
                date('Y-m-d H:i:s', $data['stop_id']),
            ]);
        }

        //有过会话记录的
        if (! empty($data['has_conversation'])) {
            $query->andWhere(['has_conversation' => true]);
        }

        // 排序条件
        $order_by = "group_create_time";
        if (isset($data["order_by"])) {
            $order_by = $data["order_by"];
        }

        $res = $query->orderBy([$order_by => SORT_DESC])->paginate($data["page"] ?? 1, $data["size"] ?? 1);
        if (! $res['items']->isEmpty()) {
            $staffListIndex = [];
            foreach ($res["items"] as $group) {
                /** @var GroupModel $group */
                $staffList = array_filter($group->get('member_list'), function ($node) {
                    return $node["type"] == 1;
                });
                $group->unset('member_list');
                $staffListIndex = array_merge($staffListIndex, ArrayHelper::index($staffList, "userid"));
            }

            // 群主名称
            foreach ($res['items'] as $group) {
                $staff = $staffListIndex[$group->get('owner')];
                $group->append('owner_name', $staff['name'] ?? '');
            }
        }

        // 上次同步时间
        $res['last_sync_time'] = date("Y-m-d H:i:s", strtotime($corp->get('sync_group_time')));

        return $res;
    }
}
