<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\KeywordsTagging;

use Common\Cron;
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
    public function init(): void
    {

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
            //敏感词触发统计，30秒一次
            Cron::name('msg_check_keywords')->spec('@every 30s')
                ->action(MsgCheckKeywordsConsumer::class, ['corp' => $corp]),
        ];
    }

    public function getConsumerRouters(): array
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
}
