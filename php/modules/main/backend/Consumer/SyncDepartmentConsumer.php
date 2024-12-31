<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Consumer;

use Modules\Main\Enum\EnumUserRoleType;
use Modules\Main\Model\ChatConversationsModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\DepartmentModel;
use Modules\Main\Model\StaffModel;
use Modules\Main\Model\StaffTagModel;
use Modules\Main\Model\UserModel;
use Throwable;

/**
 * @author rand
 * @ClassName SyncDepartmentConsumer
 * @date 2024/11/114:51
 * @description 部门、员工、标签同步
 */
class SyncDepartmentConsumer
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
        // 遍历部门员工
        $this->syncDepartmentList();

        // 遍历员工标签
        $this->syncUserTagList($this->corp);

        // 同步完了，更新一下上次同步时间
        $this->corp->update(['sync_staff_time' => now()]);

    }

    /**
     * Notes: 同步部门员工列表
     * User: rand
     * Date: 2024/11/6 14:22
     * @return void
     * @throws Throwable
     */
    public function syncDepartmentList(): void
    {
        // 查询库里面所有的部门列表
        $dbDepartmentIds = DepartmentModel::query()->getAll()->map(fn($item) => $item->get('department_id'))->toArray();

        $res = $this->corp->getWechatApi("/cgi-bin/department/simplelist");
        if (empty($res['department_id'])) {
            return;
        }

        $allDepartmentIds = array_column($res["department_id"], "id");

        // 遍历部门列表
        foreach ($res["department_id"] as $department) {
            $detailRes = $this->corp->getWechatApi("/cgi-bin/department/get?id=" . $department["id"]);
            DepartmentModel::updateOrCreate(['and',
                ['department_id' => $department['id'] ?? 0],
                ['corp_id' => $this->corp->get('id')],
            ], [
                'department_id' => $department['id'] ?? 0,
                'corp_id' => $this->corp->get('id'),
                'department_leader' => $detailRes["department"]["department_leader"] ?? [],
                'parent_id' => $department["parentid"] ?? 0,
                'order' => $department["order"] ?? 0,
                'name' => $detailRes["department"]["name"] ?? "",
            ]);

            // 去同步一下员工列表
            $this->syncUserList($this->corp, $department["id"]);
        }

        // 对比删除的部门
        $diffDepartment = array_diff($dbDepartmentIds, $allDepartmentIds);
        DepartmentModel::query()
            ->where(['and',
                ['corp_id' => $this->corp->get('id')],
                ['in', 'department_id', $diffDepartment],
            ])
            ->deleteAll();

    //    同步完了，把当前企业的超管账户更新一下
        $userInfo = UserModel::query()->where(["corp_id"=>$this->corp->get("id"),"role_id"=>EnumUserRoleType::SUPPER_ADMIN->value])->getOne();
        if (!empty($userInfo)) {
            StaffModel::query()->where(["corp_id"=>$this->corp->get("id"),"userid"=>$userInfo->get("userid")])->update([
                "role_id"=>$userInfo->get("role_id")->value,
                "can_login"=>$userInfo->get("can_login")
            ]);
        }

    }


    /**
     * @param CorpModel $corpModel
     * @param $departmentId
     * Notes: 同步员工列表
     * User: rand
     * Date: 2024/11/1 16:18
     * @return void
     * @throws Throwable
     */
    public function syncUserList(CorpModel $corpModel, $departmentId)
    {
        $res = $corpModel->getWechatApi("/cgi-bin/user/list?department_id=" . $departmentId);
        if (empty($res["userlist"])) {
            return;
        }

        //当前部门下的所有员工状态变更一下
        StaffModel::query()->where(["corp_id" => $this->corp->get('id'), "main_department" => $departmentId])->update([
            "status" => 0
        ]);

        foreach ($res["userlist"] as $userInfo) {
            $updateData = [
                'corp_id' => $this->corp->get('id'),
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
            ];

            $whereData = ['corp_id' => $this->corp->get('id'),'userid' => $userInfo['userid']];
            $hisData = StaffModel::query()->where($whereData)->getOne();
            if (empty($hisData)) {
                //同步新增的账户，没有登陆权限，普通员工角色
                $updateData["can_login"] = 0;
                $updateData["role_id"] = EnumUserRoleType::NORMAL_STAFF;
                StaffModel::create($updateData);
            } else {
                StaffModel::query()->where($whereData)->update($updateData);
            }

            // 从会话数据中查找该客户有没有会话记录
            $conversation = ChatConversationsModel::query()
                ->where(['corp_id' => $this->corp->get('id')])
                ->andWhere(['or',
                    ['from' => $userInfo['userid']],
                    ['to' => $userInfo['userid']],
                ])
                ->getOne();
            if (!empty($conversation)) {
                StaffModel::hasConversationSave($this->corp, $userInfo['userid']);
            }
        }

        //状态为0 的变更为已离职
        StaffModel::query()->where(["corp_id" => $this->corp->get('id'), "main_department" => $departmentId, "status" => 0])->update([
            "status" => 5
        ]);
    }

    /**
     * @param CorpModel $corpModel
     * Notes: 同步员工标签列表
     * User: rand
     * Date: 2024/11/6 10:26
     * @return void
     * @throws Throwable
     */
    public function syncUserTagList(CorpModel $corpModel): void
    {
        $res = $corpModel->getWechatApi("/cgi-bin/tag/list");
        if (empty($res["taglist"])) {
            return;
        }

        // 库里有的，
        $allStaffTagList = StaffTagModel::query()->where(["corp_id" => $this->corp->get('id')])->getAll();
        $allStaffTagIds = [];
        foreach ($allStaffTagList as $item) {
            $allStaffTagIds[] = $item->get('tag_id');
        }

        // 用户标签列表
        $staffUserIdsIndex = [];

        // 遍历更新写入
        foreach ($res["taglist"] as $tagInfo) {
            StaffTagModel::updateOrCreate(['and',
                ["corp_id" => $this->corp->get('id')],
                ["tag_id" => $tagInfo["tagid"]],
            ], [
                "corp_id" => $this->corp->get('id'),
                "tag_id" => $tagInfo["tagid"],
                "tag_name" => $tagInfo["tagname"] ?? "",
            ]);

            // 同步一下当前标签下的员工列表
            $userIds = $this->syncTagStaffList($corpModel, $tagInfo["tagid"] ?? "");

            if (!empty($userIds)) {
                foreach ($userIds as $userId) {
                    if (!array_key_exists($userId, $staffUserIdsIndex)) {
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
                ['corp_id' => $this->corp->get('id')],
                ['in', 'tag_id', $deleteTagList],
            ])
            ->deleteAll();

        // 获取所有员工列表，更新标签关联
        $allStaff = StaffModel::query()->where(['corp_id' => $this->corp->get('id')])->getAll();
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
     * @throws Throwable
     */
    public function syncTagStaffList(CorpModel $corpModel, $tagId): array
    {
        $res = $corpModel->getWechatApi("/cgi-bin/tag/get?tagid=" . $tagId);

        if (empty($res["userlist"])) {
            return [];
        }

        return array_column($res["userlist"], "userid");
    }
}
