<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\UserPermission\Service;


use Basis\Nats\Message\Payload;
use Common\Job\Producer;
use Common\Yii;
use Modules\Main\Enum\EnumUserRoleType;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\StaffModel;
use Modules\Main\Model\UserRoleModel;
use Modules\UserPermission\Consumer\UpdateStaffRoleConsumer;
use Modules\UserPermission\Model\OtherRoleModel;
use Modules\UserPermission\Model\RolePermissionDetailModel;
use Modules\UserPermission\Model\UserRoleDetailModel;
use Yiisoft\Arrays\ArrayHelper;

/**
 * @author rand
 * @ClassName StatisticService
 * @date 2024/12/314:42
 * @description
 */
class PermissionService
{
    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 获取角色列表
     * User: rand
     * Date: 2024/12/30 18:30
     * @return array|mixed[]
     * @throws \Throwable
     */
    public static function getList(CorpModel $corp, $data)
    {
        //先获取默认的角色列表
        $userRole = UserRoleModel::query()->where(["!=", "id", EnumUserRoleType::VISITOR->value])->orderBy(["id" => SORT_DESC])->getAll()->toArray();

        //获取自定义角色列表
        $query = OtherRoleModel::query()->where(["corp_id" => $corp->get("id")]);
        if (!empty($data["role_id"])) {
            $query->andWhere(["id" => $data["role_id"]]);
        }
        $otherUserRole = $query->orderBy(["id" => SORT_ASC])->getAll();

        $otherUserRoleArr = [];
        if (!empty($otherUserRole)) {
            $otherUserRoleArr = $otherUserRole->toArray();
        }

        if (empty($data["role_id"])) {
            $allRoleList = array_merge($userRole, $otherUserRoleArr);
        } else {
            $allRoleList = $otherUserRoleArr;
        }

        $totalSql = " select count(id) as total,role_id from main.staff group by role_id ";
        $totalRes = Yii::db()->createCommand($totalSql)->queryAll();
        foreach ($totalRes as $item) {
            $staffRoleIndex[$item["role_id"]] = $item["total"] ?? 0;
        }


        //全部权限
        $allPermissionList = RolePermissionDetailModel::query()->where(["corp_id" => $corp->get("id")])->getAll()->toArray();

        $permissionList = [];
        foreach ($allPermissionList as $item) {
            foreach ($item["permission_detail"]["child"] as $node) {
                if ($node["status"] == 1) {
                    $permissionList[] = $node;
                }
            }
        }

        $permissionListKey = ArrayHelper::index($permissionList, "permission_key");

        foreach ($allRoleList as &$item) {
            $item["staff_total"] = $staffRoleIndex[$item["id"]] ?? 0;

            $item["permission_detail"] = [];
            foreach ($item["permission_config"] as $permissionKey) {
                if (array_key_exists($permissionKey, $permissionListKey)) {
                    $item["permission_detail"][] = $permissionListKey[$permissionKey];
                }
            }

        }

        return $allRoleList;

    }

    /**
     * @param CorpModel $corp
     * Notes: 角色下员工列表
     * User: rand
     * Date: 2024/12/31 09:25
     * @return array|mixed[]
     * @throws \Throwable
     */
    public static function getUserList(CorpModel $corp, $data)
    {
        $query = StaffModel::query()->select(["id", "name", "userid", "has_conversation", "role_id"])->where([
            "corp_id" => $corp->get("id"),
            "role_id" => $data["role_id"] ?? 0,
        ]);

        if (!empty($data["keyword"])) {
            $query->andWhere(["ilike", "name", $data["keyword"]]);
        }

        $list = $query->orderBy(["id" => SORT_DESC])->paginate($data["page"] ?? 1, $data["size"] ?? 10);

        $allRole = self::getList($corp, []);
        $allRoleIndex = ArrayHelper::index($allRole, "id");

        foreach ($list["items"] as &$item) {
            $item->append("role_name", $allRoleIndex[$item->get("role_id")]["role_name"] ?? "");
        }

        return $list;
    }

    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 保存更新权限
     * User: rand
     * Date: 2024/12/30 18:38
     * @return void
     */
    public static function saveRole(CorpModel $corp, $data)
    {

        $updateData = [
            "corp_id" => $corp->get("id"),
            "role_name" => $data["role_name"] ?? "",
            "permission_config" => $data["permission_config"] ?? [],
        ];

        $query = OtherRoleModel::query()->where(["corp_id" => $corp->get("id"), "role_name" => $data["role_name"] ?? ""]);

        if (!empty($data["id"])) {//更新权限
            $query->andWhere(["!=", "id", $data["id"]]);
        }

        $hisData = $query->getOne();
        if (!empty($hisData)) {
            throw new \Exception("存在相同的角色名");
        }

        if (!empty($data["id"])) {//更新权限
            OtherRoleModel::query()->where(["corp_id" => $corp->get("id"), "id" => $data["id"]])->update($updateData);
        } else {//新建权限
            OtherRoleModel::create($updateData);
        }

        //更新一下角色权限
        Producer::dispatch(UpdateStaffRoleConsumer::class, ["corp" => $corp]);

        return;
    }


    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 更新账户权限
     * User: rand
     * Date: 2024/12/31 09:11
     * @return void
     */
    public static function changeRole(CorpModel $corp, $data)
    {

        //更新主模块账户角色
        $changeData = [
            "corp_id" => $corp->get("id"),
            "new_role_id" => $data["new_role_id"],
            "staff_userid" => $data["staff_userid"],
        ];
        Yii::getNatsClient()->request('main.change_staff_role', json_encode($changeData), function (Payload $response) {
            ddump($response->body);
        });

        //更新员工角色列表
        foreach ($data["staff_userid"] as $staff_userid) {
            $whereData = ["corp_id" => $corp->get("id"), "staff_userid" => $staff_userid];
            $updateData = ["other_role_id" => $data["new_role_id"]];
            UserRoleDetailModel::updateOrCreate($whereData, array_merge($whereData, $updateData));
        }

        //更新一下角色权限
        Producer::dispatch(UpdateStaffRoleConsumer::class, ["corp" => $corp]);

        return;
    }

    /**
     * @param CorpModel $corp
     * @param $role_id
     * Notes: 删除角色
     * User: rand
     * Date: 2024/12/31 10:03
     * @return void
     * @throws \Throwable
     */
    public static function delete(CorpModel $corp, $role_id)
    {
        if ($role_id < 100) {
            throw new \Exception("当前角色不可删除");
        }

        //更新主模块账户角色
        $changeData = [
            "corp_id" => $corp->get("id"),
            "role_id" => $role_id,
            "new_role_id" => EnumUserRoleType::NORMAL_STAFF->value,
        ];
        Yii::getNatsClient()->request('main.change_staff_role', json_encode($changeData), function (Payload $response) {
            ddump($response->body);
        });

        //删除角色
        OtherRoleModel::query()->where([
            "corp_id" => $corp->get("id"),
            "id" => $role_id
        ])->deleteAll();

        //删除角色关联
        UserRoleDetailModel::query()->where([
            "corp_id" => $corp->get("id"),
            "other_role_id" => $role_id
        ])->deleteAll();


        //更新一下角色权限
        Producer::dispatch(UpdateStaffRoleConsumer::class, ["corp" => $corp]);

        return;
    }

    /**
     * @param CorpModel $corp
     * Notes: 全部角色列表
     * User: rand
     * Date: 2025/1/2 09:43
     * @return array|mixed[]
     * @throws \Throwable
     */
    public static function permissionList(CorpModel $corp)
    {
        $allPermission = RolePermissionDetailModel::query()->where(["corp_id" => $corp->get("id")])->orderBy(["permission_index" => SORT_ASC])->getAll()->toArray();

        $permissionList = [];
        foreach ($allPermission as $item) {
            if (!array_key_exists($item["permission_index"], $permissionList)) {
                $permissionList[$item["permission_index"]] = [
                    "permission_index" => $item["permission_index"],
                    "server_name" => $item["server_name"],
                    "description" => $item["description"],
                    "permission_list" => [],
                ];
            }
            $permissionList[$item["permission_index"]]["permission_list"][] = [
                "permission_node_title" => $item["permission_node_title"],
                "permission_key" => $item["permission_key"],
                "permission_detail" => $item["permission_detail"]
            ];
        }

        return $permissionList;
    }

}
