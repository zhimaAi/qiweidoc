<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Services;

use App\Models\CustomerTagModel;
use App\Models\StaffTagModel;
use App\Models\UserModel;
use Throwable;

class TagsService
{

    /**
     * @param UserModel $currentUser
     * @return iterable
     * @throws Throwable
     */
    public static function staff(UserModel $currentUser): iterable
    {
        return StaffTagModel::query()->where(["corp_id" => $currentUser->get('corp_id')])->getAll();
    }

    /**
     * @param UserModel $currentUser
     * Notes: 所有客户标签列表
     * User: rand
     * Date: 2024/11/8 14:48
     * @return iterable
     * @throws Throwable
     */
    public static function customer(UserModel $currentUser): iterable
    {
        //查询标签组
        $allCustomerTags = CustomerTagModel::query()
            ->select(["tag_id", "name", "tag_type", "group_id", "order"])
            ->where(["corp_id" => $currentUser->get('corp_id')])
            ->orderBy(["order" => SORT_ASC])
            ->getAll()
            ->toArray();

        $returnData = [];
        foreach ($allCustomerTags as $index => $allCustomerTag) {
            if ($allCustomerTag['tag_type'] == 1 && ! array_key_exists($allCustomerTag['tag_id'], $returnData)) {
                $returnData[$allCustomerTag['tag_id']] = [
                    "group_tag_id" => $allCustomerTag['tag_id'],
                    "group_tag_name" => $allCustomerTag['name'],
                    "tag_list" => [],
                ];
                unset($allCustomerTags[$index]);
            }
        }

        //实际标签
        foreach ($allCustomerTags as $allCustomerTag) {
            $returnData[$allCustomerTag['group_id']]["tag_list"][] = [
                "tag_id" => $allCustomerTag['tag_id'],
                "tag_name" => $allCustomerTag['name'],
            ];
        }

        return array_values($returnData);
    }
}
