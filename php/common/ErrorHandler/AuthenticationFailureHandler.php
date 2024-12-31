<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Common\ErrorHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\DataResponse\Formatter\JsonDataResponseFormatter;
use Yiisoft\Http\Status;

class AuthenticationFailureHandler implements RequestHandlerInterface
{
    public function __construct(
        protected DataResponseFactoryInterface $responseFactory,
        protected JsonDataResponseFormatter $jsonDataResponseFormatter,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse([
                'status' => "failed",
                'error_message' => "登录过期",
                'error_code' => Status::UNAUTHORIZED,
                'data' => [],
            ], Status::UNAUTHORIZED)
            ->withResponseFormatter($this->jsonDataResponseFormatter);
    }
}
