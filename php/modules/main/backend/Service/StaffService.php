<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Service;

use Common\Job\Producer;
use Modules\Main\Consumer\SyncStaffChatConsumer;
use Modules\Main\Enum\EnumUserRoleType;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\DepartmentModel;
use Modules\Main\Model\StaffModel;
use Modules\Main\Model\StaffTagModel;
use Modules\Main\Model\UserModel;
use Modules\Main\Model\UserRoleModel;
use Throwable;
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
     * @throws Throwable
     * @throws Exception
     * @throws InvalidConfigException
     */
    public static function list(CorpModel $corp, $data): array
    {
        // 同步一下会话存档员工状态
        Producer::dispatch(SyncStaffChatConsumer::class, ['corp' => $corp]);

        $query = StaffModel::query()->where(['corp_id' => $corp->get('id')]);

        // 搜索关键字
        if (!empty($data["keyword"])) {
            $query->andWhere(['or',
                ['ilike', 'name', $data["keyword"]],
                ['userid' => $data['keyword']],
            ]);
        }

        // 是否在会话存档中
        if (!empty($data['chat_status'])) {
            $query->andWhere(["chat_status" => $data["chat_status"]]);
        }

        // 员工ID筛选
        if (!empty($data["userid"])) {
            $query->andWhere(["userid" => $data["userid"]]);
        }

        // 部门筛选
        if (!empty($data["department_id"])) {
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

        $query->orderBy(['id' => SORT_DESC]);


        //排序字段
        if (!empty($data["order_fields"]) && !empty($data["order_by"]) && in_array($data["order_fields"], ["cst_total"])) {
            $query->orderBy([$data["order_fields"] => $data["order_by"]]);
        }

        $res = $query->paginate($data['page'] ?? 1, $data['limit'] ?? 10);

        if (!$res['items']->isEmpty()) {

            //员工角色列表
            $userRoleList = UserRoleModel::query()->select(["id","role_name"])->getAll()->toArray();
            $userRoleListIndex = ArrayHelper::index($userRoleList,"id");

            // 获取员工标签列表
            $allTagsId = [];
            foreach ($res["items"] as $item) {
                /** @var DepartmentModel $item */
                $allTagsId = array_values(array_unique(array_filter(array_merge($allTagsId, $item->get('tag_ids')))));
            }

            $staffTagList = [];
            // 查询标签
            if (!empty($allTagsId)) {
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
                if (!empty($item->get('tag_ids'))) {
                    foreach ($item->get('tag_ids') as $tagId) {
                        $tagName[] = $staffTagListIndex[$tagId]['tag_name'];
                    }
                }
                $item->append('tag_name', $tagName);
                $item->append('role_info', $userRoleListIndex[$item->get("role_id")->value]);
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
                $department = $departmentListIndex[$staff->get('main_department')] ?? [];
                $staff->append('department_name', $department['name'] ?? '');
            }
        }

        // 上次同步时间
        $res['last_sync_time'] = $corp->get('sync_staff_time');

        return $res;
    }

    /**
     * @param CorpModel $corp
     * @param UserModel $user
     * @param $data
     * Notes: 变更账户可登陆状态
     * User: rand
     * Date: 2024/12/11 19:46
     * @return void
     */
    public static function changeLogin(CorpModel $corp, UserModel $user, $data): void
    {

        if (!in_array($user->get("role_id"), [EnumUserRoleType::ADMIN, EnumUserRoleType::SUPPER_ADMIN])) {
            throw new \Exception("您不是管理员，不可进行此操作");
        }

        $staffInfo = StaffModel::query()->where(["corp_id" => $corp->get("id"), "id" => $data["id"]])->getOne();

        if (empty($staffInfo)) {
            throw new \Exception("账号不存在");
        }

        if ($staffInfo->get("role_id") == EnumUserRoleType::SUPPER_ADMIN && $data["can_login"] == 0) {
            throw new \Exception("超级管理员不可关闭登陆权限");
        }

        StaffModel::query()->where(["corp_id" => $corp->get("id"), "id" => $data["id"]])->update([
            "can_login" => $data["can_login"]
        ]);

        //继续变更一下用户表
        $where = ["corp_id" => $corp->get("id"), "userid" => $staffInfo->get("userid")];
        $update = [
            "can_login" => $data["can_login"],
            "role_id"=>$staffInfo->get("role_id")->value
        ];

        $changeRes = UserModel::query()->where($where)->update($update);

        if (empty($changeRes)) {
            $update["account"] = $staffInfo->get("userid");
            UserModel::firstOrCreate($where,array_merge($where,$update));
        }

        return;
    }


    /**
     * @param CorpModel $corp
     * @param UserModel $user
     * @param $data
     * Notes: 变更账户角色
     * User: rand
     * Date: 2024/12/11 19:46
     * @return void
     */
    public static function changeRole(CorpModel $corp, UserModel $user, $data): void
    {

        if ($user->get("role_id") !=  EnumUserRoleType::SUPPER_ADMIN) {
            throw new \Exception("您不是超级管理员，不可进行此操作");
        }

        $staffInfo = StaffModel::query()->where(["corp_id" => $corp->get("id"), "id" => $data["id"]])->getOne();

        if (empty($staffInfo)) {
            throw new \Exception("账号不存在");
        }

        StaffModel::query()->where(["corp_id" => $corp->get("id"), "id" => $data["id"]])->update([
            "role_id" => $data["role_id"]
        ]);

        //继续变更一下用户表
        $where = ["corp_id" => $corp->get("id"), "userid" => $staffInfo->get("userid")];
        $update = [
            "can_login" => $staffInfo->get("can_login"),
            "role_id" => $data["role_id"],
        ];

        $changeRes = UserModel::query()->where($where)->update($update);

        if (empty($changeRes)) {
            $update["account"] = $staffInfo->get("userid");
            UserModel::firstOrCreate($where,array_merge($where,$update));
        }

        return;
    }
}
