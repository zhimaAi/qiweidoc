<?php

namespace Modules\CustomerTag;

use Common\RouterProvider;
use Modules\CustomerTag\Controller\TagController;
use Modules\Main\Library\Middlewares\CurrentCorpInfoMiddleware;
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
            Group::create('/api')
                ->middleware(Authentication::class)
                ->middleware(CurrentCorpInfoMiddleware::class)
                ->routes(
                    Route::get('/tags')->action([TagController::class, 'list']),
                    Route::post('/tags')->action([TagController::class, 'updateOrCreate']),
                    Route::delete('/tags/group/{group_id:.+}')->action([TagController::class, 'destroyGroup']),
                    Route::delete('/tags/{tag_id:.+}')->action([TagController::class, 'destroyTag']),
                ),
        ];
    }

    public function getConsoleRouters(): array
    {
        return [];
    }

    public function getConsumerRouters(): array
    {
        return [];
    }
}
