<?php

namespace Modules\Main\Consumer;

use Common\Yii;
use Modules\Main\Model\CorpModel;
use Modules\Main\Service\ChatSessionPullService;
use Throwable;

class SyncSessionMessageConsumer
{
    public function __construct()
    {
        
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $corp = CorpModel::query()->getOne();

        if (!empty($corp) && !empty($corp->get('chat_private_key'))) {
            $mutexKey = self::class . $corp->get('id');
            if (Yii::mutex()->acquire($mutexKey)) {
                ChatSessionPullService::handleMessage($corp);
                Yii::mutex()->release($mutexKey);
            }
        }
    }
}
