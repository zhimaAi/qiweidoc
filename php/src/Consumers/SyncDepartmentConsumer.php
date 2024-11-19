<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Consumers;

use App\Libraries\Core\Consumer\BaseConsumer;
use App\Models\CorpModel;
use App\Models\DepartmentModel;
use App\Models\StaffModel;
use App\Models\StaffTagModel;

/**
 * @author rand
 * @ClassName SyncDepartmentConsumer
 * @date 2024/11/114:51
 * @description 部门、员工、标签同步
 */
class SyncDepartmentConsumer extends BaseConsumer
{
    private CorpModel $corpInfo;

    public function __construct(CorpModel $corpInfo)
    {
        $this->corpInfo = $corpInfo;
    }

    public function handle()
    {
        // 遍历部门员工
        $this->syncDepartmentList();

        // 遍历员工标签
        $this->syncUserTagList($this->corpInfo);

        // 同步完了，更新一下上次同步时间
        $this->corpInfo->update(['sync_staff_time' => now()]);

    }

    /**
     * Notes: 同步部门员工列表
     * User: rand
     * Date: 2024/11/6 14:22
     * @return void
     * @throws \App\Libraries\Core\Exception\WechatRequestException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Throwable
     */
    public function syncDepartmentList()
    {
        // 查询库里面所有的部门列表
        $dbDepartmentIds = DepartmentModel::query()->getAll()->map(fn ($item) => $item->get('department_id'))->toArray();

        $res = $this->corpInfo->getWechatApi("/cgi-bin/department/simplelist");
        if (empty($res['department_id'])) {
            return;
        }

        $allDepartmentIds = array_column($res["department_id"], "id");

        // 遍历部门列表
        foreach ($res["department_id"] as $department) {
            $detailRes = $this->corpInfo->getWechatApi("/cgi-bin/department/get?id=" . $department["id"]);
            DepartmentModel::updateOrCreate(['and',
                ['department_id' => $department['id'] ?? 0],
                ['corp_id' => $this->corpInfo->get('id')],
            ], [
                'department_id' => $department['id'] ?? 0,
                'corp_id' => $this->corpInfo->get('id'),
                'department_leader' => $detailRes["department"]["department_leader"] ?? [],
                'parent_id' => $department["parentid"] ?? 0,
                'order' => $department["order"] ?? 0,
                'name' => $detailRes["department"]["name"] ?? "",
            ]);

            // 去同步一下员工列表
            $this->syncUserList($this->corpInfo, $department["id"]);
        }

        // 对比删除的部门
        $diffDepartment = array_diff($dbDepartmentIds, $allDepartmentIds);
        DepartmentModel::query()
            ->where(['and',
                ['corp_id' => $this->corpInfo->get('id')],
                ['in', 'department_id', $diffDepartment],
            ])
            ->deleteAll();
    }


    /**
     * @param CorpModel $corpModel
     * @param $departmentId
     * Notes: 同步员工列表
     * User: rand
     * Date: 2024/11/1 16:18
     * @return void
     */
    public function syncUserList(CorpModel $corpModel, $departmentId)
    {
        $res = $corpModel->getWechatApi("/cgi-bin/user/list?department_id=" . $departmentId);
        if (empty($res["userlist"])) {
            return;
        }

        foreach ($res["userlist"] as $userInfo) {
            StaffModel::updateOrCreate(['and',
                ['corp_id' => $this->corpInfo->get('id')],
                ['userid' => $userInfo['userid']],
            ], [
                'corp_id' => $this->corpInfo->get('id'),
                "userid" => $userInfo["userid"] ?? "",
                "name" => $userInfo["name"] ?? "",
                "position" => $userInfo["position"] ?? "",
                "status" => $userInfo["status"] ?? 0,
                "enable" => $userInfo["enable"] ?? 0,
                "isleader" => $userInfo["isleader"] ?? 0,
                "main_department" => $userInfo["main_department"] ?? 0,
                "alias" => $userInfo["alias"] ?? "",
                "department" => $userInfo["department"] ?? [],
                "extattr" => $userInfo["extattr"] ?? [],
                "order" => $userInfo["order"] ?? [],
                "is_leader_in_dept" => $userInfo["is_leader_in_dept"] ?? [],
                "direct_leader" => $userInfo["direct_leader"] ?? [],
                "tag_ids" => $userInfo["tag_ids"] ?? [],
            ]);
        }
    }

    /**
     * @param CorpModel $corpModel
     * Notes: 同步员工标签列表
     * User: rand
     * Date: 2024/11/6 10:26
     * @return void
     */
    public function syncUserTagList(CorpModel $corpModel)
    {
        $res = $corpModel->getWechatApi("/cgi-bin/tag/list");
        if (empty($res["taglist"])) {
            return;
        }

        // 库里有的，
        $allStaffTagList = StaffTagModel::query()->where(["corp_id" => $this->corpInfo->get('id')])->getAll();
        $allStaffTagIds = [];
        foreach ($allStaffTagList as $item) {
            $allStaffTagIds[] = $item->get('tag_id');
        }

        // 用户标签列表
        $staffUserIdsIndex = [];

        // 遍历更新写入
        foreach ($res["taglist"] as $tagInfo) {
            StaffTagModel::updateOrCreate(['and',
                ["corp_id" => $this->corpInfo->get('id')],
                ["tag_id" => $tagInfo["tagid"]],
            ], [
                "corp_id" => $this->corpInfo->get('id'),
                "tag_id" => $tagInfo["tagid"],
                "tag_name" => $tagInfo["tagname"] ?? "",
            ]);

            // 同步一下当前标签下的员工列表
            $userIds = $this->syncTagStaffList($corpModel, $tagInfo["tagid"] ?? "");

            if (! empty($userIds)) {
                foreach ($userIds as $userId) {
                    if (! array_key_exists($userId, $staffUserIdsIndex)) {
                        $staffUserIdsIndex[$userId] = [];
                    }
                    $staffUserIdsIndex[$userId][] = $tagInfo["tagid"];
                }
            }
        }

        // 本次查询出来的
        $wechatTagIds = array_column($res["taglist"], "tagid");

        // 本次删除的标签列表
        $deleteTagList = array_values(array_diff($allStaffTagIds, $wechatTagIds));
        StaffTagModel::query()
            ->where(['and',
                ['corp_id' => $this->corpInfo->get('id')],
                ['in', 'tag_id', $deleteTagList],
            ])
            ->deleteAll();

        // 获取所有员工列表，更新标签关联
        $allStaff = StaffModel::query()->where(['corp_id' => $this->corpInfo->get('id')])->getAll();
        foreach ($allStaff as $staff) {
            /** @var StaffModel $staff */
            $staff->update(['tag_ids' => $staffUserIdsIndex[$staff->get('userid')] ?? []]);
        }
    }


    /**
     * @param CorpModel $corpModel
     * @param $tagId
     * Notes: 获取标签下的员工ID
     * User: rand
     * Date: 2024/11/6 14:33
     * @return array
     * @throws \App\Libraries\Core\Exception\WechatRequestException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function syncTagStaffList(CorpModel $corpModel, $tagId)
    {
        $res = $corpModel->getWechatApi("/cgi-bin/tag/get?tagid=" . $tagId);

        if (empty($res["userlist"])) {
            return [];
        }

        return array_column($res["userlist"], "userid");
    }
}
