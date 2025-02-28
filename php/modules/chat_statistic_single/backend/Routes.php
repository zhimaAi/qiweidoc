<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\ChatStatisticSingle;

use Common\Cron;
use Common\Job\Consumer;
use Common\RouterProvider;
use Modules\ChatStatisticSingle\Cron\CheckExpireCron;
use Modules\ChatStatisticSingle\Model\ConfigModel;
use Modules\Main\Library\Middlewares\CurrentCorpInfoMiddleware;
use Modules\Main\Library\Middlewares\UserRoleMiddleware;
use Modules\Main\Model\CorpModel;
use Modules\ChatStatisticSingle\Consumer\StatisticsSingleChatConsumer;
use Modules\ChatStatisticSingle\Controller\StatisticController;
use Yiisoft\Auth\Middleware\Authentication;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

class Routes extends RouterProvider
{
    // 默认初始化配置
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
    ];

    public function getHttpRouters(): array
    {
        return [
            Group::create("/api/single/chat/stat")
                ->middleware(Authentication::class)
                ->middleware(CurrentCorpInfoMiddleware::class)
                ->middleware(UserRoleMiddleware::class)
                ->routes(
                    Route::get('/config')->action([StatisticController::class, 'getConfig'])->defaults(["permission_key"=>"chat_statistic_single.list"]),//获取配置
                    Route::post('/config')->action([StatisticController::class, 'saveConfig'])->defaults(["permission_key"=>"chat_statistic_single.edit"]),//保存配置
                    Route::get('/total')->action([StatisticController::class, 'getTotal'])->defaults(["permission_key"=>"chat_statistic_single.list"]),//获取统计汇总
                    Route::get('/list')->action([StatisticController::class, 'getList'])->defaults(["permission_key"=>"chat_statistic_single.list"]),//获取统计列表
                    Route::get('/detail')->action([StatisticController::class, 'getDetail'])->defaults(["permission_key"=>"chat_statistic_single.list"]),//获取明细
                    Route::get('/stat')->action([StatisticController::class, 'stat'])->defaults(["permission_key"=>"chat_statistic_single.list"]),//获取明细
                )
        ];
    }

    public function getConsumerRouters(): array
    {
        return [
            Consumer::name("chat_statistic_single")->count(1)->action(StatisticsSingleChatConsumer::class),
        ];
    }

    public function init(): void
    {
        $corp = CorpModel::query()->getOne();
        $filter = ["corp_id" => $corp->get("id")];
        ConfigModel::firstOrCreate($filter, array_merge($filter, self::DefaultConfig));

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
            //单聊统计，每小时0点触发
            Cron::name('chat_statistic_single')->spec('0 * * * *')
                ->action(StatisticsSingleChatConsumer::class, ['corp' => $corp]),

            // 验证模块有效期
            Cron::name('check-expire')->spec('* * * * *')
                ->action(CheckExpireCron::class, []),
        ];
    }
}
