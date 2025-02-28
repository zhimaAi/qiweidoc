<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Consumer;

use Common\Yii;
use Modules\Main\Model\ChatConversationsModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\CustomersModel;
use Modules\Main\Model\CustomerTagModel;
use Modules\Main\Model\StaffModel;
use Throwable;

/**
 * Notes:客户同步
 * User: rand
 * Date: 2024/11/6 19:12
 */
class SyncCustomersConsumer
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
        Yii::logger()->info("准备同步客户数据");
        $mutexKey = self::class . $this->corp->get('id');
        if (Yii::mutex(100)->acquire($mutexKey)) {
            try {
                Yii::logger()->info("开始同步客户数据");
                $this->_handle();
            } catch (Throwable $e) {
                Yii::logger()->error($e);
            }

            Yii::mutex()->release($mutexKey);
        }
    }

    /**
     * @throws Throwable
     */
    public function _handle(): void
    {
        $limit = 100;

        $staffInfoList = StaffModel::query()->where(['corp_id' => $this->corp->get('id')])->andWhere(["!=", "status", 5])->getAll();
        $staffInfoSplit = arraySplit($staffInfoList->toArray(), 1);

        foreach ($staffInfoSplit as $item) {
            $reqData = [
                "userid_list" => [],
                "limit" => $limit,
            ];

            // 获取本组需要查询的员工列表
            foreach ($item as $node) {
                $reqData["userid_list"][] = $node['userid'];
            }

            $cursor = "";
            while (true) {
                if (!empty($cursor)) {
                    $reqData["cursor"] = $cursor;
                }
                try {
                    $customerRes = $this->corp->postWechatApi("/cgi-bin/externalcontact/batch/get_by_user", $reqData, "json");
                } catch (Throwable $e) {
                    Yii::logger()->error(json_encode($reqData));
                    Yii::logger()->error($e);
                    break;
                }
                if (empty($customerRes["external_contact_list"])) {
                    break;
                }
                foreach ($customerRes["external_contact_list"] as $node) {
                    $staffUserId = $node["follow_info"]["userid"] ?? "";
                    $externalUserid = $node["external_contact"]["external_userid"] ?? "";

                    $data = [
                        "staff_remark" => $node["follow_info"]["remark"] ?? "",
                        "staff_description" => $node["follow_info"]["description"] ?? "",
                        "add_time" => date('Y-m-d H:i:s', $node["follow_info"]["createtime"] ?? 0),
                        "staff_tag_id_list" => $node["follow_info"]["tag_id"] ?? [],
                        "staff_remark_mobiles" => $node["follow_info"]["remark_mobiles"] ?? [],
                        "add_way" => $node["follow_info"]["add_way"] ?? 0,
                        "oper_userid" => $node["follow_info"]["oper_userid"] ?? "",
                        "external_userid" => $node["external_contact"]["external_userid"] ?? "",
                        "external_name" => $node["external_contact"]["name"] ?? "",
                        "external_type" => $node["external_contact"]["type"] ?? 1,
                        "external_profile" => $node["external_contact"]["external_profile"] ?? [],
                        "avatar" => $node["external_contact"]["avatar"] ?? "",
                        "corp_name" => $node["external_contact"]["corp_name"] ?? "",
                        "corp_full_name" => $node["external_contact"]["corp_full_name"] ?? "",
                        "gender" => $node["external_contact"]["gender"] ?? 0,
                        "add_status" => 2,
                    ];

                    $customerModel = CustomersModel::query()
                        ->where(['and',
                            ["corp_id" => $this->corp->get('id')],
                            ["staff_userid" => $staffUserId],
                            ["external_userid" => $externalUserid],
                        ])
                        ->getOne();

                    if (empty($customerModel)) {
                        $customerModel = CustomersModel::create(array_merge([
                            "corp_id" => $this->corp->get('id'),
                            "staff_userid" => $staffUserId,
                        ], $data));

                        //所有客户需要更新到标签表
                        foreach ($node["follow_info"]["tag_id"] as $tagNode) {
                            CustomerTagModel::addCustomerTag($this->corp, $tagNode, $customerModel->get('id'));
                        }
                    } else {
                        //客户标签更新
                        $newTagList = $node["follow_info"]["tag_id"] ?? [];
                        $removeTag = array_values(array_diff($customerModel->get('staff_tag_id_list'), $newTagList));
                        $addTag = array_values(array_diff($newTagList, $customerModel->get('staff_tag_id_list')));

                        foreach ($addTag as $tagNode) {
                            CustomerTagModel::addCustomerTag($this->corp, $tagNode, $customerModel->get('id'));
                        }

                        foreach ($removeTag as $tagNode) {
                            CustomerTagModel::removeCustomerTag($this->corp, $tagNode, $customerModel->get('id'));
                        }

                        $data['add_status'] = 2;
                        $customerModel->update($data);
                    }

                    // 从会话数据中查找该客户有没有会话记录
                    $conversation = ChatConversationsModel::query()
                        ->where(['corp_id' => $this->corp->get('id')])
                        ->andWhere(['or',
                            ['from' => $customerModel->get('external_userid')],
                            ['to' => $customerModel->get('external_userid')],
                        ])
                        ->getOne();
                    if (!empty($conversation)) {
                        CustomersModel::hasConversationSave($this->corp, $customerModel->get('external_userid'));
                    }
                }

                if (empty($customerRes["next_cursor"])) {
                    break;
                } else {
                    $cursor = $customerRes["next_cursor"];
                }
            }

            $cursor = "";
        }
        // 同步完了，更新一下上次同步时间
        $this->corp->update(['sync_customer_time' => now()]);

        // 更新好友状态，先把状态 1 的改为0，，再把状态2 的改为1
        CustomersModel::query()
            ->where(['and',
                ['corp_id' => $this->corp->get('id')],
                ['add_status' => 1],
            ])
            ->update(['add_status' => 0]);
        CustomersModel::query()
            ->where(['and',
                ['corp_id' => $this->corp->get('id')],
                ['add_status' => 2],
            ])
            ->update(['add_status' => 1]);

        // 统计更新客户总数
        $staffCusTotal = CustomersModel::query()
            ->select("count(external_userid) as total, staff_userid")
            ->where(['and',
                ['corp_id' => $this->corp->get('id')],
                ['>', 'add_status', 0],
            ])
            ->groupBy('staff_userid')
            ->getAll();
        foreach ($staffCusTotal as $item) {
            StaffModel::query()
                ->where(['and',
                    ['corp_id' => $this->corp->get('id')],
                    ['userid' => $item->get('staff_userid')],
                ])
                ->update(['cst_total' => ($item->get('total') ?? 0)]);
        }
    }
}
