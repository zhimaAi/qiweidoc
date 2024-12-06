<?php

namespace Modules\Main\Controller;

use Common\Controller\BaseController;
use Common\Yii;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

class ModuleController extends BaseController
{
    public function getModuleList(ServerRequestInterface $request): ResponseInterface
    {
        $moduleList = Yii::getDefaultRpcClient()->call('module.List', '');
        $moduleList = array_merge(array_filter($moduleList, function ($module) {
            return $module['name'] != 'main';
        }));

        return $this->jsonResponse($moduleList);

    }

    public function getModuleDetail(ServerRequestInterface $request, #[RouteArgument('name')] string $name): ResponseInterface
    {
        $moduleInfo = Yii::getDefaultRpcClient()->call('module.Info', $name);

        return $this->jsonResponse($moduleInfo);
    }

    public function enableModule(#[RouteArgument('name')] string $name): ResponseInterface
    {
        Yii::getDefaultRpcClient()->call('module.Enable', $name);

        return $this->jsonResponse();
    }

    public function disableModule(#[RouteArgument('name')] string $name): ResponseInterface
    {
        Yii::getDefaultRpcClient()->call('module.Disable', $name);

        return $this->jsonResponse();
    }
}
