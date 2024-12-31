<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Model;

use Common\DB\BaseModel;
use Common\Yii;
use Throwable;

class CustomerTagModel extends BaseModel
{
    public function getTableName(): string
    {
        return "main.customer_tag";
    }

    protected function casts(): array
    {
        return [
            "id" => 'int',
            "created_at" => 'string',
            "updated_at" => 'string',
            "corp_id" => 'string',
            "tag_id" => 'string',
            "external_userid" => 'binary',
        ];
    }


    /**
     * @param CorpModel $corp
     * @param $tagId
     * @param $customerId
     * Notes: 客户添加到标签关联
     * User: rand
     * Date: 2024/11/25 17:43
     * @return void
     *
     * @throws Throwable
     */
    public static function addCustomerTag(CorpModel $corp, $tagId, $customerId): void
    {
        $tag = self::query()->where(['tag_id' => $tagId])->getOne();
        if (empty($tag)) {
            $InsertSql = /** @lang sql */
                "INSERT INTO main.customer_tag
(\"corp_id\",\"tag_id\",\"external_userid\") VALUES
('" . $corp->get('id') . "','{$tagId}',rb_build('{}'))";
            Yii::db()->createCommand($InsertSql)->execute();
        }

        $updateTagCstSql = /** @lang sql */
            "UPDATE main.customer_tag
SET external_userid = rb_or(external_userid, '{" . $customerId . "}'::roaringbitmap)
WHERE tag_id = '{$tagId}' and corp_id = '{$corp->get('id')}'";
        Yii::db()->createCommand($updateTagCstSql)->execute();
    }

    /**
     * @param $tagId
     * @param $customerId
     * Notes: 从标签关联中移除客户
     * User: rand
     * Date: 2024/11/25 17:45
     * @return void
     *
     * @throws Throwable
     */
    public static function removeCustomerTag(CorpModel $corp, $tagId, $customerId): void
    {
        $updateTagCstSql = /** @lang sql */
            "UPDATE main.customer_tag
set external_userid = rb_remove(external_userid,'{$customerId}')
WHERE tag_id = '{$tagId}' and corp_id = '{$corp->get('id')}'";
        Yii::db()->createCommand($updateTagCstSql)->execute();
    }
}
