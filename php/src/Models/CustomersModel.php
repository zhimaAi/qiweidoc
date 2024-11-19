<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Models;

use App\Libraries\Core\DB\BaseModel;

class CustomersModel extends BaseModel
{
    public function getTableName(): string
    {
        return "customers";
    }

    protected function getPrimaryKeys(): string | array
    {
        return "id";
    }

    protected function casts(): array
    {
        return [
            'staff_tag_id_list' => 'array',
            'staff_remark_mobiles' => 'array',
            'external_profile' => 'array',
        ];
    }

    /**
     * @throws \Throwable
     */
    public static function hasConversationSave(CorpModel $corp, string $externalUserid)
    {
        $customer = self::query()
            ->where(['and',
                ['corp_id' => $corp->get('id')],
                ['external_userid' => $externalUserid],
            ])
            ->getOne();
        if (!empty($customer)) {
            $customer->update(['has_conversation' => true]);
        }
    }
}
