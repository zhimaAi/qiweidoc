<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace Modules\CustomBrand;

use Common\Cron;
use Common\RouterProvider;
use Modules\Main\Library\Middlewares\CurrentCorpInfoMiddleware;
use Modules\Main\Library\Middlewares\UserRoleMiddleware;
use Yiisoft\Auth\Middleware\Authentication;
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
            // 验证模块有效期
            Cron::name('check_expire')->spec('* * * * *')->action(CheckExpireCron::class, []),
        ];
    }

    public function getConsumerRouters(): array
    {
        return [];
    }

    public function getHttpRouters(): array
    {
        return [
            Route::get('/api/settings')
                ->action([SettingController::class, 'list']),

            Route::post('/api/settings')
                ->middleware(Authentication::class)
                ->middleware(CurrentCorpInfoMiddleware::class)
                ->middleware(UserRoleMiddleware::class)
                ->action([SettingController::class, 'store'])
                ->defaults(["permission_key" => 'custom_brand.edit','filter_status'=>true]),
        ];
    }
}
