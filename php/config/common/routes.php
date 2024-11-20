<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

declare(strict_types=1);

use App\Controller\AuthController;
use App\Controller\ChatController;
use App\Controller\CorpController;
use App\Controller\CustomersController;
use App\Controller\DepartmentController;
use App\Controller\FrontController;
use App\Controller\GroupController;
use App\Controller\StaffController;
use App\Controller\TagsController;
use App\Controller\UserController;
use App\Libraries\Middlewares\CurrentCorpInfoMiddleware;
use Yiisoft\Auth\Middleware\Authentication;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

return [
    // 渲染前端
    Route::get('/')->action([FrontController::class, 'management']),
    Route::get('/ping')->action([FrontController::class, 'pingPong']),
    Route::get('/WW_verify_{content:.+\.txt}')->action([FrontController::class, 'verify']),

    // 认证相关的接口
    Group::create('/api/auth')->routes(
        Route::post('/code/login')->action([AuthController::class, 'codeLogin']),
        Route::post('/password/login')->action([AuthController::class, 'passwordLogin']),
    ),

    // 接入流程
    Group::create('/api/corps')->routes(
        Route::post('/init/check')->action([CorpController::class, 'checkInit']),
        Route::post('/basic')->action([CorpController::class, 'initCorpInfo']),
    ),

    // 需要验证登录的接口
    Group::create("/api/")
        ->middleware(Authentication::class)
        ->middleware(CurrentCorpInfoMiddleware::class)
        ->routes(

            // 企业相关接口
            Route::get('/corps/current')->action([CorpController::class, 'getCurrentCorpInfo']),
            Route::get('/corps/session/publicKey')->action([CorpController::class,'getSessionPublicKey']),
            Route::post('/corps/session/saveConfig')->action([CorpController::class,'saveConfig']),
            Route::put('/corps/current')->action([CorpController::class, 'updateConfig']),

            // 用户相关接口
            Route::get('/users/current')->action([UserController::class, 'getCurrentUserInfo']),
            Route::put('/users/current')->action([UserController::class, 'updateCurrentUserInfo']),

            // 群相关的接口
            Route::get('/groups/sync')->action([GroupController::class, 'sync']),
            Route::get('/groups/list')->action([GroupController::class, 'list']),

            // 客户相关的接口
            Route::get('/customers/sync')->action([CustomersController::class, 'sync']),
            Route::get('/customers/list')->action([CustomersController::class, 'list']),

            // 员工相关接口
            Route::get('/staff/list')->action([StaffController::class, 'list']),

            // 部门相关接口
            Route::get('/department/sync')->action([DepartmentController::class, 'sync']),
            Route::get('/department/list')->action([DepartmentController::class, 'list']),

            // 标签相关的接口
            Route::get('/tags/staff')->action([TagsController::class, 'staff']),
            Route::get('/tags/customer')->action([TagsController::class, 'customer']),

            // 会话存档相关的接口
            Route::get('/chats/by/staff/customer/conversation/list')->action([ChatController::class, 'getCustomerConversationListByStaff']),
            Route::get('/chats/by/staff/staff/conversation/list')->action([ChatController::class, 'getStaffConversationListByStaff']),
            Route::get('/chats/by/staff/room/conversation/list')->action([ChatController::class, 'getRoomConversationListByStaff']),
            Route::get('/chats/by/customer/staff/conversation/list')->action([ChatController::class, 'getStaffConversationListByCustomer']),
            Route::get('/chats/by/conversation/message/list')->action([ChatController::class, 'getMessageListByConversation']),
            Route::get('/chats/by/group/message/list')->action([ChatController::class, 'getMessageListByGroup']),
            Route::get('/chats/search')->action([ChatController::class, 'getSearch']),
    ),
];
