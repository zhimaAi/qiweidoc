<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\HintKeywords;

use Common\Cron;
use Common\RouterProvider;
use Modules\HintKeywords\Consumer\StatisticsHintConsumer;
use Modules\HintKeywords\Controller\IndexController;
use Modules\HintKeywords\Model\NoticeConfig;
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
        $corp = CorpModel::query()->getOne();

        NoticeConfig::updateOrCreate(["corp_id" => $corp->get('id')],
            [
                "corp_id" => $corp->get('id'),
                "statistics_msg_time" => now(),
            ]
        );
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
            Cron::name('statistics_hint')->spec('@every 30s')
                ->action(StatisticsHintConsumer::class, ['corp' => $corp]),
        ];
    }

    public function getConsumerRouters(): array
    {
        return [];
    }

    public function getHttpRouters(): array
    {
        return [
            Group::create("/api/hint/keywords")
                ->middleware(Authentication::class)
                ->middleware(CurrentCorpInfoMiddleware::class)
                ->middleware(UserRoleMiddleware::class)
                ->routes(
                    Route::get('/list')->action([IndexController::class, 'list']),
                    Route::post('/delete')->action([IndexController::class, 'delete']),
                    Route::post('/save')->action([IndexController::class, 'save']),
                    Route::get('/rule/list')->action([IndexController::class, 'ruleList']),
                    Route::post('/rule/save')->action([IndexController::class, 'ruleSave']),
                    Route::get('/rule/statistics')->action([IndexController::class, 'ruleStatistic']),
                    Route::get('/rule/info')->action([IndexController::class, 'ruleInfo']),
                    Route::get('/rule/detail')->action([IndexController::class, 'ruleDetail']),
                    Route::post('/rule/delete')->action([IndexController::class, 'ruleDelete']),
                    Route::post('/rule/change/status')->action([IndexController::class, 'changeStatus']),
                    Route::get('/notice/info')->action([IndexController::class, 'noticeInfo']),
                    Route::post('/notice/save')->action([IndexController::class, 'noticeSave']),
                )
        ];
    }
}


