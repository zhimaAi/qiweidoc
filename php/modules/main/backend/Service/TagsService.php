<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Service;

use Modules\Main\Model\CorpModel;
use Modules\Main\Model\StaffTagModel;
use Modules\Main\Model\UserModel;
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
     * @param CorpModel $corp
     * Notes: 客户标签
     * User: rand
     * Date: 2024/12/10 16:04
     * @return array|mixed
     * @throws Throwable
     */
    public static function customer(CorpModel $corp)
    {

        $tagListResp = $corp->postWechatApi("/cgi-bin/externalcontact/get_corp_tag_list");
        $tagGroups = $tagListResp['tag_group'] ?? [];

        // 排序函数，根据order和create_time进行排序
        $sortFunction = function ($a, $b) {
            if ($a['order'] === $b['order']) {
                return $a['create_time'] <=> $b['create_time'];
            }
            return $a['order'] <=> $b['order'];
        };

        // 对标签组进行排序
        usort($tagGroups, $sortFunction);

        // 对每个标签组中的标签进行排序
        foreach ($tagGroups as $key => $group) {
            if (!empty($group['tag'])) {
                usort($group['tag'], $sortFunction);
                $tagGroups[$key]['tag'] = $group['tag'];  // 更新排序后的标签列表
            }
        }

        return $tagGroups;
    }
}
