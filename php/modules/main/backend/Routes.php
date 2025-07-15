<?php

namespace Modules\Main;

use Common\Broadcast;
use Common\Controller\PublicController;
use Common\Cron;
use Common\Job\Consumer;
use Common\Micro;
use Common\RouterProvider;
use Common\Yii;
use Exception;
use Modules\Main\Consumer\DownloadChatSessionBitMediasConsumer;
use Modules\Main\Consumer\DownloadChatSessionMediasConsumer;
use Modules\Main\Consumer\MarkTagListener;
use Modules\Main\Consumer\QwOpenPushConsumer;
use Modules\Main\Consumer\SendEmailConsumer;
use Modules\Main\Consumer\SyncCustomersConsumer;
use Modules\Main\Consumer\SyncDepartmentConsumer;
use Modules\Main\Consumer\SyncGroupConsumer;
use Modules\Main\Cron\CheckStaffEnableArchiveCron;
use Modules\Main\Cron\DownloadMessageMediasCron;
use Modules\Main\Cron\RemoveExpiredLocalFilesCron;
use Modules\Main\Cron\SyncSessionMessageCron;
use Modules\Main\Controller\AuthController;
use Modules\Main\Controller\ChatController;
use Modules\Main\Controller\CloudStorageSettingController;
use Modules\Main\Controller\CorpController;
use Modules\Main\Controller\CustomersController;
use Modules\Main\Controller\CustomerTagController;
use Modules\Main\Controller\DepartmentController;
use Modules\Main\Controller\GroupController;
use Modules\Main\Controller\ModuleController;
use Modules\Main\Controller\OpenPushController;
use Modules\Main\Controller\StaffController;
use Modules\Main\Controller\StorageController;
use Modules\Main\Controller\TagsController;
use Modules\Main\Controller\UserController;
use Modules\Main\Cron\SyncStaffChatCron;
use Modules\Main\Library\Middlewares\CurrentCorpInfoMiddleware;
use Modules\Main\Library\Middlewares\UserRoleMiddleware;
use Modules\Main\Library\Middlewares\WxAuthMiddleware;
use Modules\Main\Controller\WxController;
use Modules\Main\Listener\TestBroadcastController;
use Modules\Main\Micro\CheckStaffEnableArchiveMicro;
use Modules\Main\Micro\TestMirco;
use Modules\Main\Micro\ChangeRolePermissionConfigMirco;
use Modules\Main\Micro\ChangeStaffRoleMirco;
use Modules\Main\Service\StorageService;
use Modules\Main\Service\UserService;
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
     * 模块刚启动时需要执行的方法
     * @throws Exception
     */
    public function init(): void
    {
        // 初始化本地存储
        StorageService::initLocalBucket();

        // 初始化用户角色
        UserService::init();
    }

    public function getBroadcastRouters(): array
    {
        return [
            Broadcast::event('test')->from('main')
                ->action(TestBroadcastController::class),

            Broadcast::event('mark_tag')->from('keywords_tagging')
                ->action(MarkTagListener::class),
        ];
    }

    public function getCronRouters(): array
    {
        return [
            Cron::name("sync_session_message")->spec("@every 5s")
                ->action(SyncSessionMessageCron::class, []),

            Cron::name("remove_expire_local_files")->spec("* * * * *")
                ->action(RemoveExpiredLocalFilesCron::class, []),

            Cron::name("sync_staff_chat")->spec("* * * * *")
                ->action(SyncStaffChatCron::class, []),

            Cron::name("check_staff_enable_archive")->spec("@every 3s")
                ->action(CheckStaffEnableArchiveCron::class, []),

            Cron::name("download_message_medias")->spec("@every 6h")
                ->action(DownloadMessageMediasCron::class, []),
        ];
    }

    public function getMicroServiceRouters(): array
    {
        return [
            Micro::name('test')->action(TestMirco::class),
            Micro::name('change_staff_role')->action(ChangeStaffRoleMirco::class),
            Micro::name('change_role_permission_config')->action(ChangeRolePermissionConfigMirco::class),
            Micro::name('check_staff_enable_archive')->action(CheckStaffEnableArchiveMicro::class),
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
            Consumer::name("sync_staff_chat")->count(1)->action(SyncStaffChatCron::class),

            Consumer::name("qw_open_push")->count(5)->action(QwOpenPushConsumer::class),

            Consumer::name("download_session_big_medias")->count(5)->action(DownloadChatSessionBitMediasConsumer::class)->reserveOnStop(),
            Consumer::name("download_session_medias")->count(2)->action(DownloadChatSessionMediasConsumer::class)->reserveOnStop(),
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
                Route::post("/basic")->action([CorpController::class, "initCorpInfo"]),
                Route::get("/name-logo/get")->action([CorpController::class, "getBaseNameAndLogo"]),
            ),

            // 企微事件推送回调
            Route::methods([Method::GET, Method::POST], "/openpush/qw/{corp_id:.+}")->action([OpenPushController::class, "qwPush"]),

            // 企微h5
            Group::create('/api/wx/h5')
                ->middleware(WxAuthMiddleware::class)
                ->routes(
                    Route::get('/get-agent-config')->action([WxController::class, 'getAgentConfig']),
                    Route::get('/conversation/messages')->action([WxController::class, 'getMessageListByConversation']),
                    Route::get('/group/messages')->action([WxController::class, 'getMessageListByGroup']),
                ),

            // 需要验证登录的接口
            Group::create("/api")
                ->middleware(Authentication::class)
                ->middleware(CurrentCorpInfoMiddleware::class)
                ->middleware(UserRoleMiddleware::class)
                ->routes(
                    // 模块管理
                    Route::get("/modules")->action([ModuleController::class, "getModuleList"]),
                    Route::get("/modules/{name:.+}")->action([ModuleController::class, "getModuleDetail"]),
                    Route::put("/modules/{name:.+}/enable")->action([ModuleController::class, "enableModule"])->defaults(["permission_key" => 'main.plug_module.edit']),
                    Route::put("/modules/{name:.+}/disable")->action([ModuleController::class, "disableModule"])->defaults(["permission_key" => 'main.plug_module.edit']),
                    Route::post('/modules/{name:.+}/install')->action([ModuleController::class, 'installModule']),

                    // 云存储配置
                    Route::get('/ping/settings')->action([PublicController::class, 'ping']),
                    Route::get('/cloud-storage-settings')->action([CloudStorageSettingController::class, 'show'])->defaults(["permission_key" => 'main.oss_config.list']),
                    Route::put('/cloud-storage-settings')->action([CloudStorageSettingController::class, 'save'])->defaults(["permission_key" => 'main.oss_config.edit']),
                    Route::get('/cloud-storage-settings/provider-regions')->action([CloudStorageSettingController::class, 'getStorageProviderRegionList'])->defaults(["permission_key" => 'main.oss_config.list']),

                    // 文件存储
                    Route::post('/storages')->action([StorageController::class, 'upload']),

                    // 企业相关接口
                    Route::get("/corps/current")->action([CorpController::class, "getCurrentCorpInfo"]),
                    Route::get("/corps/session/publicKey")->action([CorpController::class, "getSessionPublicKey"]),
                    Route::post("/corps/session/saveConfig")->action([CorpController::class, "saveConfig"])->defaults(["permission_key" => 'main.corp_setting.edit']),
                    Route::put("/corps/current")->action([CorpController::class, "updateConfig"])->defaults(["permission_key" => 'main.corp_setting.edit']),
                    Route::get("/corps/callback/event/token/generate")->action([CorpController::class, "generateCallbackEventToken"]),
                    Route::put("/corps/callback/event/token/save")->action([CorpController::class, "saveCallbackEventToken"])->disableMiddleware(UserRoleMiddleware::class),
                    Route::post("/corps/upload/logo")->action([CorpController::class, "uploadLogo"])->defaults(["permission_key" => 'main.corp_setting.edit']),
                    Route::put("/corps/name-logo/save")->action([CorpController::class, "saveNameOrLogo"])->defaults(["permission_key" => 'main.corp_setting.edit']),

                    // 用户相关接口
                    Route::get("/users/current")->action([UserController::class, "getCurrentUserInfo"]),
                    Route::put("/users/current")->action([UserController::class, "updateCurrentUserInfo"])->defaults(["permission_key" => 'main.user_config.edit']),
                    Route::get("/demo/users/list")->action([UserController::class, "demoUserList"]),
                    Route::post("/demo/users/save")->action([UserController::class, "demoUserSave"]),
                    Route::post("/demo/users/change")->action([UserController::class, "demoUserChangeLogin"]),
                    Route::post("/demo/users/delete")->action([UserController::class, "demoUserDelete"]),
                    Route::get("/users/role/list")->action([UserController::class, "userRoleList"]),

                    // 群相关的接口
                    Route::get("/groups/sync")->action([GroupController::class, "sync"])->defaults(["permission_key" => 'main.corp_group.sync','filter_status'=>true]),
                    Route::get("/groups/list")->action([GroupController::class, "list"]),

                    // 客户相关的接口
                    Route::get("/customers/sync")->action([CustomersController::class, "sync"])->defaults(["permission_key" => 'main.customer_manager.list','filter_status'=>true]),
                    Route::get("/customers/list")->action([CustomersController::class, "list"])->defaults(["permission_key" => 'main.customer_manager.list']),
                    Route::get("/customers/has-conversation/list")->action([CustomersController::class, "hasConversationList"])->defaults(["permission_key" => 'main.customer_manager.list']),

                    // 客户标签相关的接口
                    Route::get('/customer-tags')->action([CustomerTagController::class, 'list']),
                    Route::post('/customer-tags')->action([CustomerTagController::class, 'updateOrCreate'])->defaults(["permission_key" => 'main.customer_tag.edit']),
                    Route::delete('/customer-tags/group/{group_id:.+}')->action([CustomerTagController::class, 'destroyGroup'])->defaults(["permission_key" => 'main.customer_tag.edit']),
                    Route::delete('/customer-tags/{tag_id:.+}')->action([CustomerTagController::class, 'destroyTag'])->defaults(["permission_key" => 'main.customer_tag.edit']),

                    // 员工相关接口
                    Route::get("/staff/list")->action([StaffController::class, "list"]),
                    Route::get('/staff/archive/max-num')->action([StaffController::class, 'getMaxStaffArchiveNum']),
                    Route::post('/staff/archive/enable')->action([StaffController::class, 'updateArchiveStaffEnable']),

                    // 部门相关接口
                    Route::get("/department/sync")->action([DepartmentController::class, "sync"])->defaults(["permission_key" => 'main.corp_staff.sync','filter_status'=>true]),
                    Route::get("/department/list")->action([DepartmentController::class, "list"]),

                    // 标签相关的接口
                    Route::get("/tags/staff")->action([TagsController::class, "staff"]),

                    // 会话存档相关的接口
                    Route::get("/chats/by/staff/customer/conversation/list")->action([ChatController::class, "getCustomerConversationListByStaff"])->defaults(["permission_key" => 'main.session_archive.list']),
                    Route::get("/chats/by/staff/staff/conversation/list")->action([ChatController::class, "getStaffConversationListByStaff"])->defaults(["permission_key" => 'main.session_archive.list']),
                    Route::get("/chats/by/staff/room/conversation/list")->action([ChatController::class, "getRoomConversationListByStaff"])->defaults(["permission_key" => 'main.session_archive.list']),
                    Route::get("/chats/by/customer/staff/conversation/list")->action([ChatController::class, "getStaffConversationListByCustomer"])->defaults(["permission_key" => 'main.session_archive.list']),
                    Route::get("/chats/config/info")->action([ChatController::class, "getChatConfigInfo"]),
                    Route::post("/chats/config/save")->action([ChatController::class, "saveChatConfig"]),

                    //会话存档 消息相关接口
                    Route::get('/chats/by/conversation/message/list')->action([ChatController::class, 'getMessageListByConversation'])->defaults(["permission_key" => 'main.session_archive.list']),
                    Route::get('/chats/by/group/message/list')->action([ChatController::class, 'getMessageListByGroup'])->defaults(["permission_key" => 'main.session_archive.list']),

                    //会话存档搜索相关接口
                    Route::get('/chats/search')->action([ChatController::class, 'getSearch'])->defaults(["permission_key" => 'main.session_archive.search']),

                    // 会话存档收藏相关接口
                    Route::get('/chats/by/collect/customer/conversation/list')->action([ChatController::class, 'getCustomerConversationListByCollect']),
                    Route::get('/chats/by/collect/room/conversation/list')->action([ChatController::class, 'getRoomConversationListByCollect']),
                    Route::put('/chats/join/collect')->action([ChatController::class, 'joinCollect']),
                    Route::put('/chats/cancel/collect')->action([ChatController::class, 'cancelCollect']),
                ),
        ];
    }
}
