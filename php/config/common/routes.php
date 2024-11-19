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
    // ping-pong
    Route::get('/ping')->action([FrontController::class, 'pingPong']),

    // 企微验证域名用
    Route::get('/WW_verify_{content:.+\.txt}')->action([FrontController::class, 'verify']),

    // 认证相关的接口
    Group::create('/api/auth')->routes(

        // 扫码授权登录
        Route::post('/code/login')
            ->action([AuthController::class, 'codeLogin']),

        // 账号密码登录
        Route::post('/password/login')
            ->action([AuthController::class, 'passwordLogin']),
    ),

    // 企业相关的接口
    Group::create('/api/corps')->routes(

        // 检查是否已初始化
        Route::post('/init/check')
            ->action([CorpController::class, 'checkInit']),

        // 企业基础信息保存
        Route::post('/basic')
            ->action([CorpController::class, 'initCorpInfo']),

        // 获取当前企业信息
        Route::get('/current')
            ->middleware(Authentication::class)
            ->middleware(CurrentCorpInfoMiddleware::class)
            ->action([CorpController::class, 'getCurrentCorpInfo']),

        // 获取会话存档密钥
        Route::get('/session/publicKey')
            ->middleware(Authentication::class)
            ->middleware(CurrentCorpInfoMiddleware::class)
            ->action([CorpController::class,'getSessionPublicKey']),

        // 保存会话存档配置
        Route::post('/session/saveConfig')
            ->middleware(Authentication::class)
            ->middleware(CurrentCorpInfoMiddleware::class)
            ->action([CorpController::class,'saveConfig']),

        // 更新企业信息
        Route::put('/current')
            ->middleware(Authentication::class)
            ->middleware(CurrentCorpInfoMiddleware::class)
            ->action([CorpController::class, 'updateConfig']),
    ),

    // 用户(员工)相关的接口
    Group::create('/api/users')
        ->middleware(Authentication::class)
        ->middleware(CurrentCorpInfoMiddleware::class)
        ->routes(
            // 获取当前用户信息
            Route::get('/current')
                ->action([UserController::class, 'getCurrentUserInfo']),

            // 修改密码
            Route::put('/current')
                ->action([UserController::class, 'updateCurrentUserInfo']),
        ),

    // 群相关的接口
    Group::create('/api/groups')
        ->middleware(Authentication::class)
        ->middleware(CurrentCorpInfoMiddleware::class)
        ->routes(

            // 群同步
            Route::get('/sync')->action([GroupController::class, 'sync']),

            // 群列表
            Route::get('/list')->action([GroupController::class, 'list']),
        ),

    // 客户相关的接口
    Group::create('/api/customers')
        ->middleware(Authentication::class)
        ->middleware(CurrentCorpInfoMiddleware::class)
        ->routes(

            // 客户同步
            Route::get('/sync')->action([CustomersController::class, 'sync']),

            // 客户列表
            Route::get('/list')->action([CustomersController::class, 'list']),
        ),

    // 标签相关的接口
    Group::create('/api/tags')
        ->middleware(Authentication::class)
        ->middleware(CurrentCorpInfoMiddleware::class)
        ->routes(

            // 员工标签列表
            Route::get('/staff')->action([TagsController::class, 'staff']),

            // 客户标签列表
            Route::get('/customer')->action([TagsController::class, 'customer']),
        ),

    // 会话存档相关的接口
    Group::create('/api/chats')
        ->middleware(Authentication::class)
        ->middleware(CurrentCorpInfoMiddleware::class)
        ->routes(

            // 按员工查询与客户的会话列表
            Route::get('/by/staff/customer/conversation/list')
                ->action([ChatController::class, 'getCustomerConversationListByStaff']),

            // 按员工查询与员工的会话列表
            Route::get('/by/staff/staff/conversation/list')
                ->action([ChatController::class, 'getStaffConversationListByStaff']),

            // 按员工查询与群的会话列表
            Route::get('/by/staff/room/conversation/list')
                ->action([ChatController::class, 'getRoomConversationListByStaff']),

            // 按客户查询与员工的会话列表
            Route::get('/by/customer/staff/conversation/list')
                ->action([ChatController::class, 'getStaffConversationListByCustomer']),

            // 根据会话获取聊天内容
            Route::get('/by/conversation/message/list')
                ->action([ChatController::class, 'getMessageListByConversation']),

            // 根据群聊获取聊天内容
            Route::get('/by/group/message/list')
                ->action([ChatController::class, 'getMessageListByGroup']),

            // 会话搜索
            Route::get('/search')
                ->action([ChatController::class, 'getSearch']),
        ),

    // 企业员工
    Group::create('/api/staff')
        ->middleware(Authentication::class)
        ->middleware(CurrentCorpInfoMiddleware::class)
        ->routes(

            // 员工列表
            Route::get('/list')->action([StaffController::class, 'list']),
        ),

    // 企业部门
    Group::create('/api/department')
        ->middleware(Authentication::class)
        ->middleware(CurrentCorpInfoMiddleware::class)
        ->routes(

            // 部门同步
            Route::get('/sync')->action([DepartmentController::class, 'sync']),

            // 部门列表
            Route::get('/list')->action([DepartmentController::class, 'list']),
        ),

];
