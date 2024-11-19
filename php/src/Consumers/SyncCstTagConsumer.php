<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Consumers;

use App\Libraries\Core\Consumer\BaseConsumer;
use App\Models\CorpModel;
use App\Models\CustomerTagModel;

/**
 * Notes: 客户标签同步
 * User: rand
 * Date: 2024/11/7 15:04
 */
class SyncCstTagConsumer extends BaseConsumer
{
    private CorpModel $corpInfo;

    public function __construct(CorpModel $corpInfo)
    {
        $this->corpInfo = $corpInfo;
    }

    public function handle()
    {
        // 查询标签列表
        $tagListResp = $this->corpInfo->postWechatApi("/cgi-bin/externalcontact/get_corp_tag_list");

        // 查询到的标签列表
        $newTagIdList = [];
        if (! empty($tagListResp["tag_group"])) {
            foreach ($tagListResp["tag_group"] as $item) {
                $newTagIdList[] = $item["group_id"];
                CustomerTagModel::updateOrCreate(['and',
                    ['corp_id' => $this->corpInfo->get('id')],
                    ['tag_id' => $item['group_id']],
                ], [
                    'corp_id' => $this->corpInfo->get('id'),
                    'tag_id' => $item['group_id'],
                    "name" => $item["group_name"] ?? "",
                    "tag_create_time" => date('Y-m-d H:i:s', $item["create_time"] ?? 0),
                    "order" => $item["order"] ?? 0,
                    "tag_type" => 1,
                ]);

                // 遍历实际标签
                foreach ($item["tag"] as $node) {
                    $newTagIdList[] = $node["id"];
                    CustomerTagModel::updateOrCreate(['and',
                        ['corp_id' => $this->corpInfo->get('id')],
                        ['tag_id' => $node['id']],
                    ], [
                        'corp_id' => $this->corpInfo->get('id'),
                        'tag_id' => $node['id'],
                        "name" => $node["name"] ?? "",
                        "tag_create_time" => date('Y-m-d H:i:s', $node["create_time"] ?? 0),
                        "order" => $node["order"] ?? 0,
                        "tag_type" => 2,
                        "group_id" => $item["group_id"],
                    ]);
                }

            }
        }

        // 库里面的标签列表
        $tagList = CustomerTagModel::query()->where(['corp_id' => $this->corpInfo->get('id')])->getAll();
        $hisTagIdList = array_column($tagList->toArray(), 'tag_id');

        // 需要删除的标签列表


        $removeTagId = array_diff($hisTagIdList, $newTagIdList);
        if (! empty($removeTagId)) {
            CustomerTagModel::query()->where(['in', 'tag_id', $removeTagId])->deleteAll();
        }

        // 同步客户
        SyncCustomersConsumer::dispatch(['corpInfo' => $this->corpInfo]);
    }

}
