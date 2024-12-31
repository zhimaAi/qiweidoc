<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Service;

use Modules\Main\Model\DepartmentModel;
use Modules\Main\Model\UserModel;
use Throwable;

/**
 * @author rand
 * @ClassName DepartmentService
 * @date 2024/11/114:19
 * @description
 */
class DepartmentService
{
    /**
     * @param UserModel $currentUser
     * Notes: 获取部门列表
     * User: rand
     * Date: 2024/11/1 18:19
     * @return array
     * @throws Throwable
     */
    public static function List(UserModel $currentUser)
    {
        $departmentList = DepartmentModel::query()
            ->where(['corp_id' => $currentUser->get('corp_id')])
            ->orderBy(['department_id' => SORT_ASC])
            ->getAll()
            ->toArray();

        if (empty($departmentList)) {
            return [];
        }

        foreach ($departmentList as &$item) {
            $item["child_node"] = [];
        }

        return self::buildHierarchy($departmentList);
    }

    /**
     * @param array $departments
     * @param int $parentId
     * Notes: 部门列表排序
     * User: rand
     * Date: 2024/11/5 16:58
     * @return array
     */
    public static function buildHierarchy(array &$departments, int $parentId = 0): array
    {
        $branch = [];

        foreach ($departments as &$department) {
            if ($department['parent_id'] == $parentId) {
                $children = self::buildHierarchy($departments, $department['department_id']);
                if ($children) {
                    $department['child_node'] = $children;
                }
                $branch[] = $department;
                unset($department); // 防止引用干扰
            }
        }

        return $branch;
    }

    /**
     * @param $departments
     * @param $parentId
     * @return array
     */
    public static function getSubDepartments($departments, $parentId): array
    {
        $subDepartments = [];
        foreach ($departments as $department) {
            if ($department['parent_id'] == $parentId) {
                $subDepartments[] = $department['department_id'];
                $subDepartments = array_merge($subDepartments, self::getSubDepartments($departments, $department['department_id']));
            }
        }

        return $subDepartments;
    }
}
