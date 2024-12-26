<?php

namespace Modules\TimeoutReplyGroup\Consumer;

use Modules\Main\Model\CorpModel;
use Modules\TimeoutReplyGroup\Service\RuleService;
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
