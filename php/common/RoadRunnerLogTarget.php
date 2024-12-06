<?php

namespace Common;

use RoadRunner\Logger\Logger;
use Yiisoft\Log\Target;

class RoadRunnerLogTarget extends Target
{
    private Logger $rrLogger;

    public function __construct()
    {
        $this->rrLogger = new Logger(Yii::getDefaultRpcClient());

        parent::__construct();
    }

    protected function export(): void
    {
        $text = $this->formatMessages("\n");
        $this->rrLogger->log($text);
    }
}
