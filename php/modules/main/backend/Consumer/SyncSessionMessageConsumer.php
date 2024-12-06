<?php

namespace Modules\Main\Consumer;

use Common\Yii;
use Modules\Main\Model\CorpModel;
use Modules\Main\Service\ChatSessionPullService;
use Throwable;

class SyncSessionMessageConsumer
{
    private readonly CorpModel $corp;

    public function __construct(CorpModel $corp)
    {
        $this->corp = $corp;
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $mutexKey = self::class . $this->corp->get('id');
        if (Yii::mutex()->acquire($mutexKey)) {
            ChatSessionPullService::handleMessage($this->corp);
            Yii::mutex()->release($mutexKey);
        }
    }
}
