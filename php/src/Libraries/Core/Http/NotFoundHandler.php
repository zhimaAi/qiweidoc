<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

declare(strict_types=1);

namespace App\Libraries\Core\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\DataResponse\DataResponseFormatterInterface;
use Yiisoft\Http\Status;

final class NotFoundHandler implements RequestHandlerInterface
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
                Status::NOT_FOUND,
                'Not found',
            )
        );
    }
}
