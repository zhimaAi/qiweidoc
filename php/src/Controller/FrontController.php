<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

declare(strict_types=1);

namespace App\Controller;

use App\Libraries\Core\Http\BaseController;
use App\Libraries\Core\Yii;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

class FrontController extends BaseController
{
    public function management(): ResponseInterface
    {
        $staticFile = Yii::aliases()->get('@root/../static/management/dist/index.html');
        $content = file_get_contents($staticFile);

        return $this->htmlResponse($content);
    }

    /**
     * ping pong
     */
    public function pingPong(): ResponseInterface
    {
        return $this->textResponse('pong');
    }

    /**
     * 验证企微域名
     */
    public function verify(ServerRequestInterface $request, #[RouteArgument('content')] string $content): ResponseInterface
    {
        $key = str_replace('/', '', $request->getRequestTarget());

        return $this->textResponse(Yii::cache()->psr()->get($key) ?: str_replace('.txt', '', $content));
    }
}
