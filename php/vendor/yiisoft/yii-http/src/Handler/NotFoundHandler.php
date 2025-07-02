<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Http\Handler;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;

/**
 * NotFoundHandler is used as a fallback handler by default {@see \Yiisoft\Yii\Http\Application}.
 */
final class NotFoundHandler implements RequestHandlerInterface
{
    public function __construct(private ResponseFactoryInterface $responseFactory)
    {
    }

    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request
            ->getUri()
            ->getPath();
        $response = $this->responseFactory->createResponse(Status::NOT_FOUND);
        $response
            ->getBody()
            ->write("We were unable to find the page \"$path\".");
        return $response;
    }
}
