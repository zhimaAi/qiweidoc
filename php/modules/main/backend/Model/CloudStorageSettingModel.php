<?php

namespace Modules\Main\Model;

use Common\DB\BaseModel;

class CloudStorageSettingModel extends BaseModel
{
    public function getTableName(): string
    {
        return "main.cloud_storage_setting";
    }

    protected function casts(): array
    {
        return [
            "id" => 'int',
            "created_at" => 'string',
            "updated_at" => 'string',
            "provider" => 'string',
            "region" => 'string',
            "endpoint" => 'string',
            "bucket" => 'string',
            "access_key" => 'string',
            "secret_key" => 'string',
        ];
    }
}
