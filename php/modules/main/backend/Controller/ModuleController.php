<?php

namespace Modules\Main\Controller;

use Common\Broadcast;
use Common\Controller\BaseController;
use Common\Module;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Auth\Middleware\Authentication;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

class ModuleController extends BaseController
{
    public function getModuleList(ServerRequestInterface $request): ResponseInterface
    {
        $modules = Module::getModuleDirectories();
        $modules = array_filter($modules, function ($name) {
            return $name != 'main';
        });

        $result = [];
        foreach ($modules as $moduleName) {
            $result[] = Module::getModuleConfig($moduleName);
        }
        return $this->jsonResponse($result);
    }

    public function getModuleDetail(ServerRequestInterface $request, #[RouteArgument('name')] string $name): ResponseInterface
    {
        return $this->jsonResponse(Module::getModuleConfig($name));
    }

    public function enableModule(ServerRequestInterface $request, #[RouteArgument('name')] string $name): ResponseInterface
    {
        $config = Module::getModuleConfig($name);
        if (!$config['paused']) {
            throw new LogicException("插件{$name}已经是启用状态");
        }

        $rpcPort = Module::findAvailableRpcPorts(1)[0];
        $httpPort = Module::findAvailableHttpPorts(1)[0];
        Module::enable($name, $config, $rpcPort, $httpPort);

        //发送模块开启事件
        $currentUserInfo = $request->getAttribute(Authentication::class);
        $changeMsg = [
            "corp_id" => $currentUserInfo->get("corp_id"),
            "module_name" => $name,
            "event_time" => date("Y-m-d H:i:s", time())
        ];
        Broadcast::event("module_enable")->send(json_encode($changeMsg));

        return $this->jsonResponse();
    }

    public function disableModule(#[RouteArgument('name')] string $name): ResponseInterface
    {
        $config = Module::getModuleConfig($name);
        if ($config['paused']) {
            throw new LogicException("插件{$name}已经是禁用状态");
        }
        Module::disable($name);

        return $this->jsonResponse();
    }
}
