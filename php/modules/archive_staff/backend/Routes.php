<?php

namespace Modules\ArchiveStaff;

use Common\Cron;
use Common\Micro;
use Common\RouterProvider;
use Modules\Main\Library\Middlewares\CurrentCorpInfoMiddleware;
use Modules\Main\Library\Middlewares\UserRoleMiddleware;
use Yiisoft\Auth\Middleware\Authentication;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

class Routes extends RouterProvider
{
    public function init(): void
    {
        SettingModel::firstOrCreate(['key' => 'is_staff_designated'], [
            'key' => 'is_staff_designated',
            'value' => 1,
        ]);
        SettingModel::firstOrCreate(['key' => 'max_staff_num'], [
            'key' => 'max_staff_num',
            'value' => 5,
        ]);
        SettingModel::firstOrCreate(['key' => 'enable_voice_play'], [
            'key' => 'enable_voice_play',
            'value' => 1,
        ]);
    }

    public function getBroadcastRouters(): array
    {
        return [];
    }

    public function getMicroServiceRouters(): array
    {
        return [
            Micro::name('query')->action(ArchiveSettingMicro::class),
        ];
    }

    public function getConsumerRouters(): array
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

    public function getHttpRouters(): array
    {
        return [
            Group::create('/api')
                ->middleware(Authentication::class)
                ->middleware(CurrentCorpInfoMiddleware::class)
                ->middleware(UserRoleMiddleware::class)
                ->routes(
                    Route::get('/settings')->action([SettingController::class, 'get']),
                    Route::put('/settings')->action([SettingController::class, 'set']),
                ),
        ];
    }
}
