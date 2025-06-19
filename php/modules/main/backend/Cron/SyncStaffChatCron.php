<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Cron;

use Modules\Main\Model\CorpModel;
use Modules\Main\Model\StaffModel;
use Throwable;

/**
 * Notes: 同步会话存档员工状态
 * User: rand
 * Date: 2024/11/15 17:29
 */
class SyncStaffChatCron
{
    public function __construct()
    {
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
                ['corp_id' => $corp->get('id')],
            ])
            ->getAll();
        $hisStaffChatUserid = array_column($staffUserIds->toArray(), "userid");

        $res = $corp->getWechatApi("cgi-bin/msgaudit/get_permit_user_list", [], CorpModel::SecretTypeChat);

        $chatUserIds = [];
        if (!empty($res["ids"])) {//这是开了会话存档的员工
            $chatUserIds = $res["ids"];
            StaffModel::query()
                ->where(['and',
                    ['corp_id' => $corp->get('id')],
                    ['in', 'userid', $chatUserIds],
                ])
                ->update(['chat_status' => 1]);
        }

        //没有会话存档的员工
        $removeUserid = array_diff($hisStaffChatUserid, $chatUserIds);
        StaffModel::query()
            ->where(['and',
                ['corp_id' => $corp->get('id')],
                ['chat_status' => 1],
                ['in', 'userid', $removeUserid],
            ])
            ->update(['chat_status' => 2]);
    }
}
