<?php

namespace Modules\Main\Cron;

use Common\Job\Producer;
use Modules\Main\Consumer\UploadStorageToCloudConsumer;
use Modules\Main\Model\CloudStorageSettingModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\StorageModel;
use Yiisoft\Yii\Console\ExitCode;

class UploadMessageMediasCron
{
    public function __construct()
    {
    }

    public function handle()
    {
        $corp = CorpModel::query()->getOne();
        if (empty($corp)) {
            return ExitCode::OK;
        }

        $cloudStorageSetting = CloudStorageSettingModel::query()->orderBy(['id' => SORT_DESC])->getOne();
        if (empty($cloudStorageSetting)) {
            return ExitCode::OK;
        }

        $storages = StorageModel::query()
            ->where(['cloud_storage_setting_id' => 0])
            ->orderBy(['id' => SORT_ASC])
            ->limit(1000)
            ->getAll();
        foreach ($storages as $storage) {
            Producer::dispatch(UploadStorageToCloudConsumer::class, ['storage' => $storage]);
        }
    }
}
