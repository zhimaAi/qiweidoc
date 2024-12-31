<?php

namespace Modules\TimeoutReplyGroup;

use Common\Job\Consumer;
use Common\Job\Producer;
use Common\RouterProvider;
use Common\Yii;
use Modules\Main\Library\Middlewares\CurrentCorpInfoMiddleware;
use Modules\Main\Library\Middlewares\UserRoleMiddleware;
use Modules\TimeoutReplyGroup\Consumer\RuleRunConsumer;
use Modules\TimeoutReplyGroup\Controller\ReplyRuleController;
use Modules\TimeoutReplyGroup\Controller\RuleController;
use Yiisoft\Auth\Middleware\Authentication;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

class Routes extends RouterProvider
{
    public function init(): void
    {
        Yii::logger()->info("初始化定时任务");
        Producer::dispatchCron(RuleRunConsumer::class, [], '* * * * *');
    }

    public function getBroadcastRouters(): array
    {
        return [];
    }

    public function getHttpRouters(): array
    {
        return [
            Group::create('/api')
                ->middleware(Authentication::class)
                ->middleware(CurrentCorpInfoMiddleware::class)
                ->middleware(UserRoleMiddleware::class)
                ->routes(
                    // 回复规则
                    Route::get('/reply-rules')->action([ReplyRuleController::class, 'show']),
                    Route::post('/reply-rules')->action([ReplyRuleController::class, 'save']),

                    // 超时规则
                    Route::get('/rules')->action([RuleController::class, 'list']),
                    Route::get('/rules/{id:\d+}')->action([RuleController::class, 'show']),
                    Route::post('/rules')->action([RuleController::class, 'save']),
                    Route::put('/rules/{id:\d+}')->action([RuleController::class, 'update']),
                    Route::put('/rules/{id:\d+}/enable')->action([RuleController::class, 'enable']),
                    Route::put('/rules/{id:\d+}/disable')->action([RuleController::class, 'disable']),
                    Route::delete('/rules/{id:\d+}')->action([RuleController::class, 'destroy']),
                ),
        ];
    }

    public function getConsoleRouters(): array
    {
        return [];
    }

    public function getConsumerRouters(): array
    {
        return [
            Consumer::name('check-recent-timeout-message')
                ->count(1)
                ->action(RuleRunConsumer::class),
        ];
    }

    public function getMicroServiceRouters(): array
    {
        return [];
    }
}
