<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Header;

/**
 * Adds Cross-Origin Resource Sharing (CORS) headers allowing everything to the response.
 */
final class CorsAllowAll implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $response
            ->withHeader(Header::ALLOW, '*')
            ->withHeader(Header::VARY, 'Origin')
            ->withHeader(Header::ACCESS_CONTROL_ALLOW_ORIGIN, '*')
            ->withHeader(Header::ACCESS_CONTROL_ALLOW_METHODS, 'GET,OPTIONS,HEAD,POST,PUT,PATCH,DELETE')
            ->withHeader(Header::ACCESS_CONTROL_ALLOW_HEADERS, '*')
            ->withHeader(Header::ACCESS_CONTROL_EXPOSE_HEADERS, '*')
            ->withHeader(Header::ACCESS_CONTROL_ALLOW_CREDENTIALS, 'true')
            ->withHeader(Header::ACCESS_CONTROL_MAX_AGE, '86400');
    }
}
