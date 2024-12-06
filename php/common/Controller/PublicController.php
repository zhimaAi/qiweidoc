<?php

namespace Common\Controller;

use Common\Module;
use Common\Yii;
use Psr\Http\Message\ResponseInterface;

class PublicController extends BaseController
{
    public function ping(): ResponseInterface
    {
        return $this->textResponse("pong");
    }

    public function info() : ResponseInterface
    {
        $moduleInfo = Yii::getDefaultRpcClient()->call('module.Info',  Module::getCurrentModuleName());

        return $this->jsonResponse($moduleInfo);
    }

    public function frontBuild(): ResponseInterface
    {
        $module = Module::getCurrentModuleName();
        $staticFile = Yii::aliases()->get("@modules/{$module}/public/build/index.html");
        $content = file_get_contents($staticFile);

        return $this->htmlResponse($content);
    }
}
