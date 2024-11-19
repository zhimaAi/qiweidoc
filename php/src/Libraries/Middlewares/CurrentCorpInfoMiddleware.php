<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Libraries\Middlewares;

use App\Models\CorpModel;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Yiisoft\Auth\Middleware\Authentication;

class CurrentCorpInfoMiddleware implements MiddlewareInterface
{
    /**
     * @throws Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $currentUserInfo = $request->getAttribute(Authentication::class);

        $currentCorpInfo = CorpModel::query()->where(['id' => $currentUserInfo->get('corp_id')])->getOne();

        $newRequest = $request->withAttribute(CorpModel::class, $currentCorpInfo);

        return $handler->handle($newRequest);
    }
}
