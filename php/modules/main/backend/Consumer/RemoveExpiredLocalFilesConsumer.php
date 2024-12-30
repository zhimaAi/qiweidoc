<?php

/**
 * 删除本地过期的文件
 */

namespace Modules\Main\Consumer;

use Modules\Main\Model\StorageModel;
use Modules\Main\Service\StorageService;

class RemoveExpiredLocalFilesConsumer
{
    public function __construct()
    {
    }

    public function handle()
    {
        $storages = StorageModel::query()
            ->where(['<', 'local_storage_expired_at', now()])
            ->andWhere(['is_deleted_local' => false])
            ->andWhere(['>', 'cloud_storage_setting_id', 0])
            ->orderBy(['id' => SORT_ASC])
            ->limit(100)
            ->getAll();
        foreach ($storages as $storage) {
            StorageService::removeExpiredLocalFile($storage);
        }
    }
}
