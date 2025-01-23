<?php

namespace Modules\Main\Cron;

use Modules\Main\Model\CorpModel;
use Modules\Main\Service\StaffService;

/**
 * 检查存档员工数量
 */
class CheckStaffEnableArchiveCron
{
    public function __construct()
    {
    }

    public function handle()
    {
        $corp = CorpModel::query()->getOne();
        if (empty($corp)) {
            return;
        }

        StaffService::checkStaffEnableArchive($corp);
    }
}
