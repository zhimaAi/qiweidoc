<?php

namespace Modules\Main\Consumer;

use Modules\Main\Model\StorageModel;
use Modules\Main\Service\StorageService;
use Throwable;

readonly class UploadStorageToCloudConsumer
{
    private StorageModel $storage;

    public function __construct(StorageModel $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        StorageService::saveCloud($this->storage);
    }
}
