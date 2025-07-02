<?php

declare(strict_types=1);

namespace Yiisoft\Auth\Handler;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;

/**
 * Default authentication failure handler. Responds with "401 Unauthorized" HTTP status code.
 */
final class AuthenticationFailureHandler implements RequestHandlerInterface
{
    public function __construct(private ResponseFactoryInterface $responseFactory)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(Status::UNAUTHORIZED);
        $response
            ->getBody()
            ->write('Your request was made with invalid credentials.');
        return $response;
    }
}
