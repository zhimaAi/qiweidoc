<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace Modules\HintKeywords;

use Common\Broadcast;
use Common\Job\Consumer;
use Common\Job\Producer;
use Common\RouterProvider;
use Modules\HintKeywords\Consumer\StatisticsHintConsumer;
use Modules\HintKeywords\Controller\IndexController;
use Modules\HintKeywords\Model\NoticeConfig;
use Modules\HintKeywords\Model\RuleModel;
use Modules\Main\Consumer\SyncSessionMessageConsumer;
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

    public function getConsoleRouters(): array
    {
        return [];
    }

    public function getConsumerRouters(): array
    {
        return [
            Consumer::name("statistics_hint")->count(1)->action(StatisticsHintConsumer::class),
        ];
    }

    public function init(): void
    {
        // TODO: Implement init() method.
        $corp = CorpModel::query()->getOne();
        Producer::dispatchCron(StatisticsHintConsumer::class, ["corp" => $corp], '30 seconds');//敏感词触发统计，30秒一次

        return;
    }

    public function getBroadcastRouters(): array
    {
        // TODO: Implement getBroadcastRouters() method.

        return [
            Broadcast::event('module_enable')->from('main')->handle(function (string $payload) {
                $msgData = json_decode($payload, true);
                if (isset($msgData["module_name"]) && $msgData["module_name"] == "hint_keywords") {
                    NoticeConfig::updateOrCreate(
                        ["corp_id" => $msgData["corp_id"] ?? ""],
                        [
                            "corp_id" => $msgData["corp_id"] ?? "",
                            "statistics_msg_time" => $msgData["event_time"] ?? date("Y-m-d H:i:s", time())
                        ]
                    );
                }
            }),
        ];
    }
}
