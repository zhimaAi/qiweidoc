<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

namespace Common\ErrorHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\DataResponse\Formatter\JsonDataResponseFormatter;
use Yiisoft\Http\Status;

final class NotFoundHandler implements RequestHandlerInterface
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
                'error_message' => 'Not found',
                'error_code' => Status::NOT_FOUND,
                'data' => [],
            ], Status::NOT_FOUND)
            ->withResponseFormatter($this->jsonDataResponseFormatter);
    }
}
