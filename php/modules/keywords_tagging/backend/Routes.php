<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\KeywordsTagging;

use Common\Broadcast;
use Common\Job\Consumer;
use Common\Job\Producer;
use Common\RouterProvider;
use Modules\KeywordsTagging\Consumer\MsgCheckKeywordsConsumer;
use Modules\KeywordsTagging\Controller\TaskController;
use Modules\Main\Library\Middlewares\CurrentCorpInfoMiddleware;
use Modules\Main\Library\Middlewares\UserRoleMiddleware;
use Modules\Main\Model\CorpModel;
use Yiisoft\Auth\Middleware\Authentication;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

class Routes extends RouterProvider
{
    public function getGrpcRouters(): array
    {
        return [];
    }

    public function getHttpRouters(): array
    {
        return [
            Group::create("/api/task")
                ->middleware(Authentication::class)
                ->middleware(CurrentCorpInfoMiddleware::class)
                ->middleware(UserRoleMiddleware::class)
                ->routes(
                    Route::get('/list')->action([TaskController::class, 'list']),
                    Route::put('/delete')->action([TaskController::class, 'delete']),
                    Route::post('/save')->action([TaskController::class, 'save']),
                    Route::get('/info')->action([TaskController::class, 'info']),
                    Route::get('/statistics')->action([TaskController::class, 'statistics']),
                    Route::put('/change/switch')->action([TaskController::class, 'changeStatus']),
                    Route::get('/log/list')->action([TaskController::class, 'RuleTriggerLogList']),

                )
        ];
    }

    public function getConsoleRouters(): array
    {
        return [];
    }

    public function getConsumerRouters(): array
    {
        //消费者注册
        return [
            Consumer::name("msg_check_keywords")->count(1)->action(MsgCheckKeywordsConsumer::class),
        ];
    }

    public function init(): void
    {
        $corp = CorpModel::query()->getOne();
        Producer::dispatchCron(MsgCheckKeywordsConsumer::class, ['corp'=>$corp], '30 seconds');//敏感词触发统计，30秒一次
        return;
    }

    public function getBroadcastRouters(): array
    {
        // 事件接收处理
        return [

        ];
    }

    public function getMicroServiceRouters(): array
    {
        return [];
    }
}
