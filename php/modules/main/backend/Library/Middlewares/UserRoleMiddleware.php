<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Library\Middlewares;

use Basis\Nats\Message\Payload;
use Common\Module;
use Common\Yii;
use Modules\Main\Enum\EnumUserRoleType;
use Modules\Main\Model\CorpModel;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Yiisoft\Auth\Middleware\Authentication;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\DataResponse\Formatter\JsonDataResponseFormatter;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\RouteCollectionInterface;

class UserRoleMiddleware implements MiddlewareInterface
{

    private $routeName = "";
    private $routePermission = "";//权限集
    private $routeFilterStatus = false;//过滤状态，部分接口不需要返回403

    public function __construct(
        protected DataResponseFactoryInterface $responseFactory,
        protected JsonDataResponseFormatter    $jsonDataResponseFormatter,
        private CurrentRoute                   $currentRoute,
        private RouteCollectionInterface       $routeCollection,

    )
    {

    }


    /**
     * @throws Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        foreach ($this->routeCollection->getRoutes() as $route) {
            if ($route->getData('name') == $this->currentRoute->getName()) {
                $defaults = $route->getData('defaults');
                $this->routePermission = $defaults['permission_key'] ?? '';
                $this->routeFilterStatus = $defaults['filter_status'] ?? false;
            }
        }

        $currentUserInfo = $request->getAttribute(Authentication::class);

        if (in_array($request->getMethod(), [Method::POST, Method::PUT, Method::DELETE]) && $currentUserInfo->get("role_id") == EnumUserRoleType::VISITOR->value) {
            return $this->responseFactory
                ->createResponse([
                    'status' => "failed",
                    'error_message' => '您是游客账号，不可进行此操作',
                    'error_code' => Status::OK,
                    'data' => [],
                ])
                ->withResponseFormatter($this->jsonDataResponseFormatter);
        }

        //获取模块开关状态，
        $moduleConfig = Module::getLocalModuleConfig("user_permission");
        if (isset($moduleConfig['paused']) && !$moduleConfig["paused"] && $this->routePermission != "" && $currentUserInfo->get("role_id") != EnumUserRoleType::VISITOR->value) {//用户权限模块启用，定义权限的路由 进行权限验证

            $corpInfo = $request->getAttribute(CorpModel::class);
            $checkData = [
                "role_id" => $currentUserInfo->get("role_id"),
                "permission_key" => $this->routePermission,
                "corp_id" => $corpInfo->get("id")
            ];

            //验证当前账户权限
            $permissionCheckRes = false;
            Yii::getNatsClient()->request('user_permission.check_permission', json_encode($checkData), function (Payload $response) use (&$permissionCheckRes) {
                $permissionRes = json_decode($response, true);
                $permissionCheckRes = $permissionRes["res"];
            });

            //无权限返回
            if (!$permissionCheckRes) {
                $http_code = Status::FORBIDDEN;
                //如果是实际操作的动作 或者是需要过滤的接口，http状态改为200
                if (in_array($request->getMethod(), [Method::POST, Method::PUT, Method::DELETE]) || $this->routeFilterStatus) {
                    $http_code = Status::OK;
                }
                return $this->responseFactory
                    ->createResponse([
                        'status' => "failed",
                        'error_message' => '暂无权限',
                        'error_code' => Status::FORBIDDEN,
                        'data' => [],
                    ], $http_code)
                    ->withResponseFormatter($this->jsonDataResponseFormatter);
            }

        }

        return $handler->handle($request);
    }
}
