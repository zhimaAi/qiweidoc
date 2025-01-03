<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\ChatStatisticGroup;

use Common\Job\Consumer;
use Common\Job\Producer;
use Common\RouterProvider;
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
    public function getHttpRouters(): array
    {
        return [
            Group::create("/api")
                ->middleware(Authentication::class)
                ->middleware(CurrentCorpInfoMiddleware::class)
                ->middleware(UserRoleMiddleware::class)
                ->routes(
                    Route::get('/config')->action([StatisticController::class, 'getConfig']),//获取配置
                    Route::post('/config')->action([StatisticController::class, 'saveConfig']),//保存配置
                    Route::get('/list')->action([StatisticController::class, 'getList']),//获取统计列表
                    Route::get('/detail')->action([StatisticController::class, 'getDetail']),//获取明细
                    Route::get('/stat')->action([StatisticController::class, 'stat']),//获取明细
                )
        ];
    }

    public function getConsoleRouters(): array
    {
        return [];
    }

    public function getConsumerRouters(): array
    {
        return [
            Consumer::name("chat_statistic_group")->count(1)->action(StatisticsGroupChatConsumer::class),
        ];
    }

    public function init(): void
    {
        $corp = CorpModel::query()->getOne();
        Producer::dispatchCron(StatisticsGroupChatConsumer::class, ["corp" => $corp], '0 * * * *');//敏感词触发统计，每小时0点触发
    }

    public function getBroadcastRouters(): array
    {
        return [];
    }

    public function getMicroServiceRouters(): array
    {
        return [];
    }
}
