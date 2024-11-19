<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Services;

use App\Consumers\SyncStaffChatConsumer;
use App\Models\CorpModel;
use App\Models\DepartmentModel;
use App\Models\StaffModel;
use App\Models\StaffTagModel;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;

/**
 * @author rand
 * @ClassName StaffService
 * @date 2024/11/114:19
 * @description 员工服务
 */
class StaffService
{
    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 员工列表
     * User: rand
     * Date: 2024/11/4 17:50
     * @return array
     * @throws \Throwable
     * @throws Exception
     * @throws InvalidConfigException
     */
    public static function list(CorpModel $corp, $data): array
    {
        // 同步一下会话存档员工状态
        SyncStaffChatConsumer::dispatch(['corpInfo' => $corp]);

        $query = StaffModel::query()->where(['corp_id' => $corp->get('id')]);

        // 搜索关键字
        if (! empty($data["keyword"])) {
            $query->andWhere(['or',
                ['ilike', 'name', $data["keyword"]],
                ['userid' => $data['keyword']],
            ]);
        }

        // 是否在会话存档中
        if (! empty($data['chat_status'])) {
            $query->andWhere(["chat_status" => $data["chat_status"]]);
        }

        // 员工ID筛选
        if (! empty($data["userid"])) {
            $query->andWhere(["userid" => $data["userid"]]);
        }

        // 部门筛选
        if (! empty($data["department_id"])) {
            // 查询所有部门列表
            $departmentList = DepartmentModel::query()
                ->select('department_id,name,parent_id')
                ->where(['corp_id' => $corp->get('id')])
                ->orderBy(['department_id' => SORT_ASC])
                ->getAll();

            // 获取当前部门下面的所有部门ID
            $subDepartment = DepartmentService::getSubDepartments($departmentList->toArray(), $data["department_id"]);

            $subDepartment[] = (int) $data["department_id"];

            if (empty($subDepartment)) {
                $query->andWhere(["main_department" => 0]);
            }
            if (count($subDepartment) == 1) {
                $query->andWhere(["main_department" => $data["department_id"]]);
            }
            if (count($subDepartment) > 1) {
                $query->andWhere(['in', 'main_department', $subDepartment]);
            }
        }

        $res = $query->orderBy(['id' => SORT_DESC])->paginate($data['page'] ?? 1, $data['limit'] ?? 10);

        if (! $res['items']->isEmpty()) {

            // 获取员工标签列表
            $allTagsId = [];
            foreach ($res["items"] as $item) {
                /** @var DepartmentModel $item */
                $allTagsId = array_values(array_unique(array_filter(array_merge($allTagsId, $item->get('tag_ids')))));
            }

            $staffTagList = [];
            // 查询标签
            if (! empty($allTagsId)) {
                $staffTagList = StaffTagModel::query()
                    ->select(["tag_id", "tag_name"])
                    ->where(['and',
                        ['corp_id' => $corp->get('id')],
                        ['in', 'tag_id', $allTagsId],
                    ])
                    ->getAll()
                    ->toArray();
            }

            $staffTagListIndex = ArrayHelper::index($staffTagList, 'tag_id');

            // 把标签名称附加到结果集中
            foreach ($res['items'] as $item) {
                $tagName = [];
                if (! empty($item->get('tag_ids'))) {
                    foreach ($item->get('tag_ids') as $tagId) {
                        $tagName[] = $staffTagListIndex[$tagId]['tag_name'];
                    }
                }
                $item->append('tag_name', $tagName);
            }

            // 获取部门id和名称的映射
            $departmentIdList = array_unique(array_column($res['items']->toArray(), 'main_department'));
            $departmentList = DepartmentModel::query()
                ->select(['department_id', 'name', 'corp_id'])
                ->where(['and',
                    ['corp_id' => $corp->get('id')],
                    ['in', 'department_id', $departmentIdList],
                ])
                ->getAll();
            $departmentListIndex = ArrayHelper::index($departmentList->toArray(), 'department_id');

            // 把部门名称附加到结果集中
            foreach ($res['items'] as $staff) {
                /** @var StaffModel $staff */
                $department = $departmentListIndex[$staff->get('main_department')];
                $staff->append('department_name', $department['name']);
            }
        }

        // 上次同步时间
        $res['last_sync_time'] = $corp->get('sync_staff_time');

        return $res;
    }
}
