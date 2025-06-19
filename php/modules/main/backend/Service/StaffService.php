<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Service;

use Basis\Nats\Message\Payload;
use Common\Micro;
use Common\Module;
use Common\Yii;
use Modules\Main\Enum\EnumUserRoleType;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\DepartmentModel;
use Modules\Main\Model\StaffModel;
use Modules\Main\Model\StaffTagModel;
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
        // 是否有过会话记录
        if (!empty($data['has_conversation'])) {
            $query->andWhere(['has_conversation' => $data['has_conversation']]);
        }
        $moduleConfig = Module::getLocalModuleConfig("archive_staff");
        $settings = [];
        if (isset($moduleConfig['paused']) && !$moduleConfig["paused"]) {
            $settings = Micro::call('archive_staff', 'query', '');
        }
        if (!empty($data['enable_archive'])) {
            if (empty($settings) || (isset($settings["is_staff_designated"]) && $settings["is_staff_designated"])) {
                $query->andWhere(['enable_archive' => $data['enable_archive']]);//指定会话存档员工
            }
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
            $userRoleList = UserRoleModel::query()->select(["id", "role_name"])->getAll()->toArray();

            //如果权限管理插件开启了，查一下自定义角色
            $moduleConfig = Module::getLocalModuleConfig("user_permission");
            if (isset($moduleConfig['paused']) && !$moduleConfig["paused"]) {
                $roleListParam = [
                    "corp_id" => $corp->get("id")
                ];
                $otherRoleList = [];
                Yii::getNatsClient()->request('user_permission.get_role_list', json_encode($roleListParam), function (Payload $response) use (&$otherRoleList) {
                    $otherRoleList = json_decode($response, true);
                });

                $userRoleList = array_merge($userRoleList, $otherRoleList["res"] ?? []);
            }

            $userRoleListIndex = ArrayHelper::index($userRoleList, "id");

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
                $item->append('role_info', $userRoleListIndex[$item->get("role_id")] ?? [
                    "id" => EnumUserRoleType::NORMAL_STAFF->value,
                    "role_name" => "普通员工"
                ]);
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
     * 检查会话存档员工数量
     */
    public static function checkStaffEnableArchive(CorpModel $corp)
    {
        // 默认最多5个存档员工名额
        $maxNumCount = 5;

        // 如果购买了存档员工管理模块则获取模块里配置配置值作为最大名额
        $archiveStaffModule = Module::getLocalModuleConfig('archive_staff');
        if (!empty($archiveStaffModule) && isset($archiveStaffModule['paused']) && !$archiveStaffModule['paused']) {
            $data = Micro::call('archive_staff', 'query', '');
            $maxNumCount = $data['max_staff_num'] ?? 5;
        }

        $staffList = StaffModel::query()
            ->where([
                'corp_id' => $corp->get('id'),
                'enable_archive' => true,
            ])
            ->orderBy(['id' => SORT_ASC])
            ->getAll();

        // 如果数据库中已经配置的名额没有超过最大名额就不管了
        if (count($staffList) <= $maxNumCount) {
            return;
        }

        // 计算需要禁用的数量
        $needDisableCount = count($staffList) - $maxNumCount;

        // 遍历需要禁用的员工并更新
        foreach ($staffList as $index => $staff) {
            if ($index < $needDisableCount) {
                StaffModel::query()->where(['id' => $staff->get('id')])->update(['enable_archive' => false]);
            }
        }
    }
}
