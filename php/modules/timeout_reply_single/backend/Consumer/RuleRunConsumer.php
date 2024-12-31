<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\TimeoutReplySingle\Consumer;

use Modules\Main\Model\CorpModel;
use Modules\TimeoutReplySingle\Service\RuleService;
use Throwable;

class RuleRunConsumer
{
    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $corps = CorpModel::query()->getAll();
        foreach ($corps as $corp) {
            RuleService::run($corp);
        }
    }
}
