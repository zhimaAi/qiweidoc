<?php

namespace Modules\Main\Model;

use Common\DB\BaseModel;

class StorageModel extends BaseModel
{
    const DEFAULT_BUCKET = 'default'; // 默认存储桶
    const SESSION_BUCKET = 'session'; // 企微会话存档存储桶

    const LOCAL_BUCKET_LIST = [
        self::DEFAULT_BUCKET,
        self::SESSION_BUCKET,
    ];

    public function getTableName(): string
    {
        return "main.storage";
    }

    protected function casts(): array
    {
        return [
            'id'                        => 'int',
            'created_at'                => 'string',
            'updated_at'                => 'string',

            'hash'                      => 'string',
            'original_filename'         => 'string',
            'file_extension'            => 'string',
            'mime_type'                 => 'string',
            'file_size'                 => 'int',
            'is_deleted_local'          => 'boolean',

            'local_storage_bucket'      => 'string',
            'local_storage_object_key'  => 'string',
            'local_storage_expired_at'  => 'string',

            'cloud_storage_setting_id'  => 'int',
            'cloud_storage_object_key'  => 'string',
        ];
    }
}
