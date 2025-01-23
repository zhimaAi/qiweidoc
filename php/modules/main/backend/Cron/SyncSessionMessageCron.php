<?php

namespace Modules\Main\Cron;

use Common\Yii;
use Modules\Main\Model\CorpModel;
use Modules\Main\Service\ChatSessionPullService;
use Throwable;

class SyncSessionMessageCron
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
        if (empty($corp) || empty($corp->get('chat_private_key'))) {
            return;
        }

        $mutexKey = self::class . $corp->get('id');
        if (!Yii::mutex()->acquire($mutexKey)) {
            return;
        }

        try {
            ChatSessionPullService::handleMessage($corp);
        } catch (Throwable $e) {
            Yii::logger()->error($e);
        } finally {
            Yii::mutex()->release($mutexKey);
        }
    }
}
