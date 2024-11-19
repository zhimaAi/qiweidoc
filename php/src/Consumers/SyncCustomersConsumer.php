<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Consumers;

use App\Libraries\Core\Consumer\BaseConsumer;
use App\Libraries\Core\Exception\WechatRequestException;
use App\Libraries\Core\Yii;
use App\Models\CorpModel;
use App\Models\CustomersModel;
use App\Models\CustomerTagModel;
use App\Models\StaffModel;
use EasyWeChat\Kernel\Exceptions\BadResponseException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;

/**
 * Notes:客户同步
 * User: rand
 * Date: 2024/11/6 19:12
 */
class SyncCustomersConsumer extends BaseConsumer
{
    private CorpModel $corpInfo;

    public function __construct(CorpModel $corpInfo)
    {
        $this->corpInfo = $corpInfo;
    }

    /**
     * @throws Exception
     * @throws ServerExceptionInterface
     * @throws BadResponseException
     * @throws InvalidConfigException
     * @throws RedirectionExceptionInterface
     * @throws WechatRequestException
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \Throwable
     * @throws TransportExceptionInterface
     */
    public function handle()
    {
        // 查询所有标签列表
        $cstTagList = CustomerTagModel::query()
            ->where(['and',
                ['corp_id' => $this->corpInfo->get('id')],
                ['tag_type' => 2],
            ])->getAll();
        $cstTagListIndex = ArrayHelper::index($cstTagList->toArray(), "tag_id");

        $limit = 100;

        $staffInfoList = StaffModel::query()->where(['corp_id' => $this->corpInfo->get('id')])->getAll();
        $staffInfoSplit = arraySplit($staffInfoList->toArray(), 50);

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
                if (! empty($cursor)) {
                    $reqData["cursor"] = $cursor;
                }
                $customerRes = $this->corpInfo->postWechatApi("/cgi-bin/externalcontact/batch/get_by_user", $reqData, "json");

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
                            ["corp_id" => $this->corpInfo->get('id')],
                            ["staff_userid" => $staffUserId],
                            ["external_userid" => $externalUserid],
                        ])
                        ->getOne();

                    if (empty($customerModel)) {
                        $customerModel = CustomersModel::create(array_merge([
                            "corp_id" => $this->corpInfo->get('id'),
                            "staff_userid" => $staffUserId,
                        ], $data));

                        //所有客户需要更新到标签表
                        foreach ($node["follow_info"]["tag_id"] as $tagNode) {
                            $updateTagCstSql = /** @lang sql */ "
UPDATE customer_tag
SET external_userid = rb_or(external_userid, '{" . $customerModel->get('id') . "}'::roaringbitmap)
WHERE id = " . $cstTagListIndex[$tagNode]['id'];
                            Yii::db()->createCommand($updateTagCstSql)->execute();
                        }
                    } else {
                        //客户标签更新
                        $newTagList = $node["follow_info"]["tag_id"] ?? [];
                        $removeTag = array_values(array_diff($customerModel->get('staff_tag_id_list'), $newTagList));
                        $addTag = array_values(array_diff($newTagList, $customerModel->get('staff_tag_id_list')));

                        foreach ($addTag as $tagNode) {
                            $updateTagCstSql = /** @lang sql */ "
UPDATE customer_tag
SET external_userid = rb_or(external_userid, '{" . $customerModel->get('id') . "}'::roaringbitmap)
WHERE id = " . $cstTagListIndex[$tagNode]['id'];
                            Yii::db()->createCommand($updateTagCstSql)->execute();
                        }

                        foreach ($removeTag as $tagNode) {
                            $updateTagCstSql = /** @lang sql */ "
UPDATE customer_tag
set external_userid = rb_remove(external_userid,'" . $customerModel->get('id') . "')
WHERE id = " . $cstTagListIndex[$tagNode]['id'];
                            Yii::db()->createCommand($updateTagCstSql)->execute();
                        }

                        $data['add_status'] = 2;
                        $customerModel->update($data);
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
        $this->corpInfo->update(['sync_customer_time' => now()]);


        // 更新好友状态，先把状态 1 的改为0，，再把状态2 的改为1
        CustomersModel::query()
            ->where(['and',
                ['corp_id' => $this->corpInfo->get('id')],
                ['add_status' => 1],
            ])
            ->update(['add_status' => 0]);
        CustomersModel::query()
            ->where(['and',
                ['corp_id' => $this->corpInfo->get('id')],
                ['add_status' => 2],
            ])
            ->update(['add_status' => 1]);

        // 统计更新客户总数
        $staffCusTotal = CustomersModel::query()
            ->select("count(external_userid) as total, staff_userid")
            ->where(['and',
                ['corp_id' => $this->corpInfo->get('id')],
                ['>', 'add_status', 0],
            ])
            ->groupBy('staff_userid')
            ->getAll();
        foreach ($staffCusTotal as $item) {
            StaffModel::query()
                ->where(['and',
                    ['corp_id' => $this->corpInfo->get('id')],
                    ['userid' => $item->get('staff_userid')],
                ])
                ->update(['cst_total' => ($item->get('total') ?? 0)]);
        }
    }

}
