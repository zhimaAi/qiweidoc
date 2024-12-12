<?php

namespace Common\Controller;

use Common\Broadcast;
use Common\Module;
use Common\Yii;
use LogicException;
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
        $moduleInfo = Module::getModuleConfig(Module::getCurrentModuleName());

        return $this->jsonResponse($moduleInfo);
    }

    public function frontBuild(): ResponseInterface
    {
        $module = Module::getCurrentModuleName();
        $staticFile = Yii::aliases()->get("@modules/{$module}/public/build/index.html");
        $content = file_get_contents($staticFile);

        return $this->htmlResponse($content);
    }
    
    public function broadcast(ServerRequestInterface $request) : ResponseInterface
    {
        if ($request->getHeaderLine('X-External')) {
            throw new LogicException("禁止访问");
        }

        $body = json_encode($request->getParsedBody());
        $broadcast = Broadcast::parse($body);
        if (empty($broadcast)) {
            return $this->textResponse('ignore');
        }
        
        Broadcast::dispatch($broadcast);
         
        return $this->textResponse('ok');
    }
}
