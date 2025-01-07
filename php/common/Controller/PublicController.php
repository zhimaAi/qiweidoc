<?php

namespace Common\Controller;

use Common\Module;
use Common\Yii;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PublicController extends BaseController
{
    public function ping(): ResponseInterface
    {
        return $this->textResponse("pong");
    }

    public function info() : ResponseInterface
    {
        $moduleInfo = Module::getLocalModuleConfig(Module::getCurrentModuleName());

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
