<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Service;

use Modules\Main\Model\CorpModel;
use Modules\Main\Model\CustomersModel;
use Modules\Main\Model\StaffModel;
use Throwable;
use Yiisoft\Arrays\ArrayHelper;

class CustomersService
{
    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 客户列表
     * User: rand
     * Date: 2024/11/11 18:30
     * @return array
     * @throws Throwable
     */
    public static function list(CorpModel $corp, $data): array
    {
        $query = CustomersModel::query()->where(["corp_id" => $corp->get('id')]);

        // 搜索关键字
        if (!empty($data["keyword"])) {
            $query->andWhere(['or',
                ['ilike', 'external_name', $data['keyword']],
                ['external_userid' => $data['keyword']],
            ]);
        }

        //存在添加时间筛选
        if (isset($data["add_time"])) {
            $query->andWhere(['>', 'add_time', $data['add_time']]);
        }

        //存在客户ID筛选
        if (isset($data["external_userid"])) {
            $query->andWhere(["external_userid" => $data["external_userid"]]);
        }

        //有过会话记录的
        if (!empty($data['has_conversation'])) {
            $query->andWhere(['has_conversation' => true]);
        }

        $query->orderBy(['add_time' => SORT_DESC]);
        $res = $query->orderBy(['add_time' => SORT_DESC])->paginate($data["page"] ?? 1, $data["size"] ?? 10);

        if (!$res["items"]->isEmpty()) {

            $resp = $corp->postWechatApi("/cgi-bin/externalcontact/get_corp_tag_list");
            $tagList = [];
            foreach ($resp['tag_group'] as $group) {
                $tagList = array_merge($tagList, $group['tag']);
            }
            $tagList = array_column($tagList, null, 'id');

            $tagIdList = [];
            $staffUserId = [];

            foreach ($res["items"] as $item) {
                /** @var CustomersModel $item */
                $tagIdList = array_values(array_unique(array_merge($tagIdList, $item->get('staff_tag_id_list'))));
                $staffUserId[] = $item->get('staff_userid');
            }

            $staffUserInfoIndex = [];
            if (!empty($staffUserId)) {
                $staffList = StaffModel::query()
                    ->where(['and',
                        ['corp_id' => $corp->get('id')],
                        ['in', 'userid', array_values(array_unique($staffUserId))],
                    ])
                    ->getAll()
                    ->toArray();
                $staffUserInfoIndex = ArrayHelper::index($staffList, "userid");
            }

            //填充一下标签
            foreach ($res["items"] as $item) {
                $allTagData = [];
                foreach ($item->get('staff_tag_id_list') as $node) {
                    if (!isset($tagList[$node])) {
                        continue;
                    }
                    $allTagData[] = [
                        'tag_name' => $tagList[$node]['name'],
                        'tag_id' => $node, $tagList[$node]['id'],
                    ];
                }
                $item->append("tag_data", $allTagData);
                $item->append("staff_user_name", $staffUserInfoIndex[$item->get('staff_userid')]['name'] ?? "");
            }
        }

        // 上次同步时间
        $res['last_sync_time'] = $corp->get('last_sync_time');

        return $res;
    }

    /**
     * 按客户查询聊天记录时获取有会话记录的客户列表
     * @throws Throwable
     */
    public static function hasConversationList(CorpModel $corp, int $page, int $size, array $params)
    {
        $query = CustomersModel::query()
            ->select([
                'external_userid',
                'max(add_time) as add_time',
                'max(avatar) as avatar',
                'max(external_name) as external_name',
                'max(corp_name) as corp_name',
            ])
            ->where(["corp_id" => $corp->get('id')])
            ->andWhere(['has_conversation' => true]);

        // 搜索关键字
        if (!empty($params["keyword"])) {
            $query->andWhere(['or',
                ['ilike', 'external_name', $params['keyword']],
                ['external_userid' => $params['keyword']],
            ]);
        }

        return $query->orderBy(['add_time' => SORT_DESC])
            ->groupBy(['external_userid'])
            ->paginate($page, $size);
    }
}
