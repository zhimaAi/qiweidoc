<?php

namespace Modules\TimeoutReplyGroup;

use Common\Cron;
use Common\RouterProvider;
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
        return [
            Cron::name('check-recent-timeout-message')->spec('* * * * *')
                ->action(RuleRunConsumer::class, []),
        ];
    }

    public function getConsumerRouters(): array
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
                    Route::get('/reply-rules')->action([ReplyRuleController::class, 'show'])->defaults(["permission_key" => 'timeout_reply_group.list']),
                    Route::post('/reply-rules')->action([ReplyRuleController::class, 'save'])->defaults(["permission_key" => 'timeout_reply_group.edit']),

                    // 超时规则
                    Route::get('/rules')->action([RuleController::class, 'list'])->defaults(["permission_key" => 'timeout_reply_group.list']),
                    Route::get('/rules/{id:\d+}')->action([RuleController::class, 'show'])->defaults(["permission_key" => 'timeout_reply_group.list']),
                    Route::post('/rules')->action([RuleController::class, 'save'])->defaults(["permission_key" => 'timeout_reply_group.edit']),
                    Route::put('/rules/{id:\d+}')->action([RuleController::class, 'update'])->defaults(["permission_key" => 'timeout_reply_group.edit']),
                    Route::put('/rules/{id:\d+}/enable')->action([RuleController::class, 'enable'])->defaults(["permission_key" => 'timeout_reply_group.edit']),
                    Route::put('/rules/{id:\d+}/disable')->action([RuleController::class, 'disable'])->defaults(["permission_key" => 'timeout_reply_group.edit']),
                    Route::delete('/rules/{id:\d+}')->action([RuleController::class, 'destroy'])->defaults(["permission_key" => 'timeout_reply_group.edit']),
                ),
        ];
    }
}
