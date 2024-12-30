<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace Modules\Main\Library\Middlewares;

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

class UserRoleMiddleware implements MiddlewareInterface
{

    public function __construct(
        protected DataResponseFactoryInterface $responseFactory,
        protected JsonDataResponseFormatter $jsonDataResponseFormatter,
    ) {
    }


    /**
     * @throws Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $currentUserInfo = $request->getAttribute(Authentication::class);

        if (in_array($request->getMethod(), [Method::POST, Method::PUT, Method::DELETE]) && $currentUserInfo->get("role_id") == EnumUserRoleType::VISITOR) {
            return $this->responseFactory
                ->createResponse([
                    'status' => "failed",
                    'error_message' => '您是游客账号，不可进行此操作',
                    'error_code' => Status::OK,
                    'data' => [],
                ])
                ->withResponseFormatter($this->jsonDataResponseFormatter);
        }

        return $handler->handle($request);
    }
}
