<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace Modules\Main\Consumer;

use Modules\Main\Model\CorpModel;
use Modules\Main\Model\StaffModel;
use Throwable;

/**
 * Notes: 同步会话存档员工状态
 * User: rand
 * Date: 2024/11/15 17:29
 */
class SyncStaffChatConsumer
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
        //获取所有会话存档中的员工
        $staffUserIds = StaffModel::query()
            ->where(['and',
                ['corp_id' => $this->corp->get('id')],
                ['chat_status' => 1],
            ])
            ->getAll();
        $hisStaffChatUserid = array_column($staffUserIds->toArray(), "userid");

        $res = $this->corp->getWechatApi("cgi-bin/msgaudit/get_permit_user_list", [], CorpModel::SecretTypeChat);

        $chatUserIds = [];
        if (!empty($res["ids"])) {//这是开了会话存档的员工
            $chatUserIds = $res["ids"];
            StaffModel::query()
                ->where(['and',
                    ['corp_id' => $this->corp->get('id')],
                    ['in', 'userid', $chatUserIds],
                ])
                ->update(['chat_status' => 1]);
        } else {//没查出来，把所有会话存档中的数据变更一下
            StaffModel::query()
                ->where(['and',
                    ['corp_id' => $this->corp->get('id')],
                    ['chat_status' => 1],
                ])
                ->update(['chat_status' => 2]);
        }


        //没有会话存档的员工
        $removeUserid = array_diff($hisStaffChatUserid, $chatUserIds);
        StaffModel::query()
            ->where(['and',
                ['corp_id' => $this->corp->get('id')],
                ['in', 'userid', $removeUserid],
            ])
            ->update(['chat_status' => 2]);
    }
}