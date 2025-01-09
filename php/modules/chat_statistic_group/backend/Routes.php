<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\ChatStatisticGroup;

use Common\Cron;
use Common\Job\Consumer;
use Common\RouterProvider;
use Modules\ChatStatisticGroup\Model\ConfigModel;
use Modules\Main\Library\Middlewares\CurrentCorpInfoMiddleware;
use Modules\Main\Library\Middlewares\UserRoleMiddleware;
use Modules\Main\Model\CorpModel;
use Modules\ChatStatisticGroup\Consumer\StatisticsGroupChatConsumer;
use Modules\ChatStatisticGroup\Controller\StatisticController;
use Yiisoft\Auth\Middleware\Authentication;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

class Routes extends RouterProvider
{
    const DefaultConfig = [
        "work_time" => [
            [
                "week" => [1, 2, 3, 4, 5],
                "range" => [
                    [
                        "s" => "09:00",
                        "e" => "18:00"
                    ]
                ],
            ]
        ],
        "cst_keywords" => [
            "full" => ["好的", "谢谢"],
            "half" => [],
            "msg_type_filter" => []
        ],
        "msg_reply_sec" => 3,
        "at_msg_reply_sec" => 3,
        "other_effect" => 0,
    ];

    public function init(): void
    {
        $corp = CorpModel::query()->getOne();
        //检查是否存在默认配置
        $groupConfig = ConfigModel::query()->where(['corp_id' => $corp->get('id')])->getOne();
        if (empty($groupConfig)) {
            ConfigModel::create(array_merge(self::DefaultConfig, ["corp_id" => $corp->get("id")]));
        }
    }

    public function getBroadcastRouters(): array
    {
        return [];
    }

    public function getMicroServiceRouters(): array
    {
        return [];
    }

    public function getCronRouters(): array
    {
        $corp = CorpModel::query()->getOne();

        return [
            //群聊统计，每小时0点触发
            Cron::name('chat_statistic_group')->spec('0 * * * *')
                ->action(StatisticsGroupChatConsumer::class, ['corp' => $corp]),
        ];
    }

    public function getConsumerRouters(): array
    {
        return [
            Consumer::name("chat_statistic_group")->count(1)->action(StatisticsGroupChatConsumer::class),
        ];
    }

    public function getHttpRouters(): array
    {
        return [
            Group::create("/api")
                ->middleware(Authentication::class)
                ->middleware(CurrentCorpInfoMiddleware::class)
                ->middleware(UserRoleMiddleware::class)
                ->routes(
                    Route::get('/config')->action([StatisticController::class, 'getConfig'])->defaults(["permission_key" => 'chat_statistic_group.list']),//获取配置
                    Route::post('/config')->action([StatisticController::class, 'saveConfig'])->defaults(["permission_key" => 'chat_statistic_group.edit']),//保存配置
                    Route::get('/list')->action([StatisticController::class, 'getList'])->defaults(["permission_key" => 'chat_statistic_group.list']),//获取统计列表
                    Route::get('/detail')->action([StatisticController::class, 'getDetail'])->defaults(["permission_key" => 'chat_statistic_group.list']),//获取明细
                    Route::get('/stat')->action([StatisticController::class, 'stat'])->defaults(["permission_key" => 'chat_statistic_group.list']),//获取明细
                )
        ];
    }


}
