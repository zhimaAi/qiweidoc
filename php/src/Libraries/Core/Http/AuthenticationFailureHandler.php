<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Libraries\Core\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\DataResponse\DataResponseFormatterInterface;
use Yiisoft\Http\Status;

class AuthenticationFailureHandler implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFormatterInterface $formatter,
        private DataResponseFactoryInterface $dataResponseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->formatter->format(
            $this->dataResponseFactory->createResponse(
                [],
                Status::UNAUTHORIZED,
                '登录过期',
            )
        );
    }
}
