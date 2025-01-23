<?php

namespace Modules\Main\Micro;

use Modules\Main\Model\CorpModel;
use Modules\Main\Service\StaffService;

class CheckStaffEnableArchiveMicro
{
    public function __construct(private string $payload)
    {

    }

    public function handle()
    {
        $corp = CorpModel::query()->getOne();
        if (!empty($corp)) {
            StaffService::checkStaffEnableArchive($corp);
        }

        return [];
    }
}
