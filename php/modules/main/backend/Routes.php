<?php

namespace Modules\Main;

use Common\Job\Consumer;
use Common\RouterProvider;
use Common\Yii;
use GRPC\Pinger\PingerInterface;
use Modules\Main\Command\PullChatSessionMessageCommand;
use Modules\Main\Consumer\DownloadChatSessionMediasConsumer;
use Modules\Main\Consumer\QwOpenPushConsumer;
use Modules\Main\Consumer\SendEmailConsumer;
use Modules\Main\Consumer\SyncCustomersConsumer;
use Modules\Main\Consumer\SyncDepartmentConsumer;
use Modules\Main\Consumer\SyncGroupConsumer;
use Modules\Main\Consumer\SyncSessionMessageConsumer;
use Modules\Main\Consumer\SyncStaffChatConsumer;
use Modules\Main\Controller\AuthController;
use Modules\Main\Controller\ChatController;
use Modules\Main\Controller\CorpController;
use Modules\Main\Controller\CustomersController;
use Modules\Main\Controller\DepartmentController;
use Modules\Main\Controller\GroupController;
use Modules\Main\Controller\ModuleController;
use Modules\Main\Controller\OpenPushController;
use Modules\Main\Controller\StaffController;
use Modules\Main\Controller\TagsController;
use Modules\Main\Controller\UserController;
use Modules\Main\GRPC\Pinger;
use Modules\Main\Library\Middlewares\CurrentCorpInfoMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Auth\Middleware\Authentication;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\DataResponse\Formatter\PlainTextDataResponseFormatter;
use Yiisoft\Http\Method;
use Yiisoft\Router\Group;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\Route;

class Routes extends RouterProvider
{
    /**
     * 控制台路由
     */
    public function getConsoleRouters(): array
    {
        return [
            "pull-chat-session-message" => PullChatSessionMessageCommand::class,
        ];
    }

    /**
     * 消费者路由
     */
    public function getConsumerRouters(): array
    {
        return [
            Consumer::name("send_email")->count(1)->action(SendEmailConsumer::class),

            Consumer::name("sync_department")->count(1)->action(SyncDepartmentConsumer::class),
            Consumer::name("sync_group")->count(1)->action(SyncGroupConsumer::class),
            Consumer::name("sync_customer")->count(1)->action(SyncCustomersConsumer::class),
            Consumer::name("sync_staff_chat")->count(1)->action(SyncStaffChatConsumer::class),

            Consumer::name("qw_open_push")->count(5)->action(QwOpenPushConsumer::class),

            Consumer::name("sync_session_message")->count(1)->action(SyncSessionMessageConsumer::class),
            Consumer::name("download_session_medias")->count(5)->action(DownloadChatSessionMediasConsumer::class)->reserveOnStop(),
        ];
    }

    /**
     * grpc路由
     */
    public function getGrpcRouters(): array
    {
        return [
            PingerInterface::class => Pinger::class,
        ];
    }

    /**
     * http路由
     */
    public function getHttpRouters(): array
    {
        return [
            // 企微域名验证
            Route::get("/WW_verify_{content:.+\.txt}")->action(function (
                ServerRequestInterface $request,
                #[RouteArgument('content')]
                string $content,
                DataResponseFactoryInterface $responseFactory,
                PlainTextDataResponseFormatter $plainTextDataResponseFormatter
            ) {
                $key = str_replace('/', '', $request->getRequestTarget());
                $result = Yii::cache()->psr()->get($key) ?: str_replace('.txt', '', $content);

                return $responseFactory
                    ->createResponse($result)
                    ->withResponseFormatter($plainTextDataResponseFormatter);
            }),

            // 认证相关的接口
            Group::create("/api/auth")->routes(
                Route::post("/code/login")->action([AuthController::class, "codeLogin"]),
                Route::post("/password/login")->action([AuthController::class, "passwordLogin"])
            ),

            // 接入流程
            Group::create("/api/corps")->routes(
                Route::post("/init/check")->action([CorpController::class, "checkInit"]),
                Route::post("/basic")->action([CorpController::class, "initCorpInfo"])
            ),

            // 企微事件推送回调
            Route::methods([Method::GET, Method::POST], "/openpush/qw/{corp_id:.+}")->action([OpenPushController::class, "qwPush"]),

            // 需要验证登录的接口
            Group::create("/api")
                ->middleware(Authentication::class)
                ->middleware(CurrentCorpInfoMiddleware::class)
                ->routes(
                    // 企业相关接口
                    Route::get("/corps/current")->action([CorpController::class, "getCurrentCorpInfo"]),
                    Route::get("/corps/session/publicKey")->action([CorpController::class, "getSessionPublicKey"]),
                    Route::post("/corps/session/saveConfig")->action([CorpController::class, "saveConfig"]),
                    Route::put("/corps/current")->action([CorpController::class, "updateConfig"]),
                    Route::get("/corps/callback/event/token/generate")->action([CorpController::class, "generateCallbackEventToken"]),
                    Route::put("/corps/callback/event/token/save")->action([CorpController::class, "saveCallbackEventToken"]),

                    // 用户相关接口
                    Route::get("/users/current")->action([UserController::class, "getCurrentUserInfo"]),
                    Route::put("/users/current")->action([UserController::class, "updateCurrentUserInfo"]),

                    // 群相关的接口
                    Route::get("/groups/sync")->action([GroupController::class, "sync"]),
                    Route::get("/groups/list")->action([GroupController::class, "list"]),

                    // 客户相关的接口
                    Route::get("/customers/sync")->action([CustomersController::class, "sync"]),
                    Route::get("/customers/list")->action([CustomersController::class, "list"]),
                    Route::get("/customers/has-conversation/list")->action([CustomersController::class, "hasConversationList"]),

                    // 员工相关接口
                    Route::get("/staff/list")->action([StaffController::class, "list"]),

                    // 部门相关接口
                    Route::get("/department/sync")->action([DepartmentController::class, "sync"]),
                    Route::get("/department/list")->action([DepartmentController::class, "list"]),

                    // 标签相关的接口
                    Route::get("/tags/staff")->action([TagsController::class, "staff"]),

                    // 会话存档相关的接口
                    Route::get("/chats/by/staff/customer/conversation/list")->action([ChatController::class, "getCustomerConversationListByStaff"]),
                    Route::get("/chats/by/staff/staff/conversation/list")->action([ChatController::class, "getStaffConversationListByStaff"]),
                    Route::get("/chats/by/staff/room/conversation/list")->action([ChatController::class, "getRoomConversationListByStaff"]),
                    Route::get("/chats/by/customer/staff/conversation/list")->action([ChatController::class, "getStaffConversationListByCustomer"]),

                    //会话存档 消息相关接口
                    Route::get('/chats/by/conversation/message/list')->action([ChatController::class, 'getMessageListByConversation']),
                    Route::get('/chats/by/group/message/list')->action([ChatController::class, 'getMessageListByGroup']),

                    //会话存档搜索相关接口
                    Route::get('/chats/search')->action([ChatController::class, 'getSearch']),

                    // 会话存档收藏相关接口
                    Route::get('/chats/by/collect/customer/conversation/list')->action([ChatController::class, 'getCustomerConversationListByCollect']),
                    Route::get('/chats/by/collect/room/conversation/list')->action([ChatController::class, 'getRoomConversationListByCollect']),
                    Route::put('/chats/join/collect')->action([ChatController::class, 'joinCollect']),
                    Route::put('/chats/cancel/collect')->action([ChatController::class, 'cancelCollect']),


                    // 模块管理
                    Route::get("/modules")->action([ModuleController::class, "getModuleList",]),
                    Route::get("/modules/{name:.+}")->action([ModuleController::class, "getModuleDetail"]),
                    Route::put("/modules/{name:.+}/enable")->action([ModuleController::class, "enableModule"]),
                    Route::put("/modules/{name:.+}/disable")->action([ModuleController::class, "disableModule"]),
                ),
        ];
    }
}
