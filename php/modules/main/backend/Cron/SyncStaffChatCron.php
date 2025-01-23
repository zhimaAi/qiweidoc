<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

<<<<<<<< HEAD:php/modules/main/backend/Consumer/SyncStaffChatConsumer.php
namespace Modules\Main\Consumer;
========
namespace Modules\Main\Cron;
>>>>>>>> master:php/modules/main/backend/Cron/SyncStaffChatCron.php

use Modules\Main\Model\CorpModel;
use Modules\Main\Model\StaffModel;
use Throwable;

/**
 * Notes: 同步会话存档员工状态
 * User: rand
 * Date: 2024/11/15 17:29
 */
<<<<<<<< HEAD:php/modules/main/backend/Consumer/SyncStaffChatConsumer.php
class SyncStaffChatConsumer
{
    private readonly CorpModel $corp;

    public function __construct(CorpModel $corp)
    {
        $this->corp = $corp;
========
class SyncStaffChatCron
{
    public function __construct()
    {
>>>>>>>> master:php/modules/main/backend/Cron/SyncStaffChatCron.php
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $corp = CorpModel::query()->getOne();
        if (empty($corp) || empty($corp->get('chat_private_key'))) {
            return;
        }

        //获取所有会话存档中的员工
        $staffUserIds = StaffModel::query()
            ->where(['and',
<<<<<<<< HEAD:php/modules/main/backend/Consumer/SyncStaffChatConsumer.php
                ['corp_id' => $this->corp->get('id')],
========
                ['corp_id' => $corp->get('id')],
>>>>>>>> master:php/modules/main/backend/Cron/SyncStaffChatCron.php
                ['chat_status' => 1],
            ])
            ->getAll();
        $hisStaffChatUserid = array_column($staffUserIds->toArray(), "userid");

<<<<<<<< HEAD:php/modules/main/backend/Consumer/SyncStaffChatConsumer.php
        $res = $this->corp->getWechatApi("cgi-bin/msgaudit/get_permit_user_list", [], CorpModel::SecretTypeChat);
========
        $res = $corp->getWechatApi("cgi-bin/msgaudit/get_permit_user_list", [], CorpModel::SecretTypeChat);
>>>>>>>> master:php/modules/main/backend/Cron/SyncStaffChatCron.php

        $chatUserIds = [];
        if (!empty($res["ids"])) {//这是开了会话存档的员工
            $chatUserIds = $res["ids"];
            StaffModel::query()
                ->where(['and',
<<<<<<<< HEAD:php/modules/main/backend/Consumer/SyncStaffChatConsumer.php
                    ['corp_id' => $this->corp->get('id')],
========
                    ['corp_id' => $corp->get('id')],
>>>>>>>> master:php/modules/main/backend/Cron/SyncStaffChatCron.php
                    ['in', 'userid', $chatUserIds],
                ])
                ->update(['chat_status' => 1]);
        } else {//没查出来，把所有会话存档中的数据变更一下
            StaffModel::query()
                ->where(['and',
<<<<<<<< HEAD:php/modules/main/backend/Consumer/SyncStaffChatConsumer.php
                    ['corp_id' => $this->corp->get('id')],
========
                    ['corp_id' => $corp->get('id')],
>>>>>>>> master:php/modules/main/backend/Cron/SyncStaffChatCron.php
                    ['chat_status' => 1],
                ])
                ->update(['chat_status' => 2]);
        }


        //没有会话存档的员工
        $removeUserid = array_diff($hisStaffChatUserid, $chatUserIds);
        StaffModel::query()
            ->where(['and',
<<<<<<<< HEAD:php/modules/main/backend/Consumer/SyncStaffChatConsumer.php
                ['corp_id' => $this->corp->get('id')],
========
                ['corp_id' => $corp->get('id')],
>>>>>>>> master:php/modules/main/backend/Cron/SyncStaffChatCron.php
                ['in', 'userid', $removeUserid],
            ])
            ->update(['chat_status' => 2]);
    }
}
