<?php

declare(strict_types=1);

namespace Yiisoft\ErrorHandler\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Yiisoft\Injector\Injector;

use function is_int;
use function is_callable;

/**
 * `ExceptionResponder` maps certain exceptions to custom responses.
 */
final class ExceptionResponder implements MiddlewareInterface
{
    /**
     * The `$exceptionMap` specified as an array can be in one of the following two formats:
     *
     * - callable format: `[\LogicException::class => callable, \DomainException::class => callable, ...]`
     * - int format: `[\Exception::class => 404, \DomainException::class => 500, ...]`
     *
     * When an exception is thrown, the map in callable format allows to take control of the response.
     * Сallable must return `Psr\Http\Message\ResponseInterface`. If specified exception classes are equal,
     * then the first one will be processed. Below are some examples:
     *
     * ```php
     * $exceptionMap = [
     *     DomainException::class => function (\Psr\Http\Message\ResponseFactoryInterface $responseFactory) {
     *         return $responseFactory->createResponse(\Yiisoft\Http\Status::CREATED);
     *     },
     *     MyHttpException::class => static fn (MyHttpException $exception) => new MyResponse($exception),
     * ]
     * ```
     *
     * When an exception is thrown, the map in int format allows to send the response with set http code.
     * If specified exception classes are equal, then the first one will be processed. Below are some examples:
     *
     * ```php
     * $exceptionMap = [
     *     \DomainException::class => \Yiisoft\Http\Status::BAD_REQUEST,
     *     \InvalidArgumentException::class => \Yiisoft\Http\Status::BAD_REQUEST,
     *     MyNotFoundException::class => \Yiisoft\Http\Status::NOT_FOUND,
     * ]
     * ```
     *
     * @param callable[]|int[] $exceptionMap A callable that must return a `ResponseInterface` or response status code.
     * @param bool $checkResponseBody Whether executing `getBody()` on response needs to be done. It's useful for
     * catching exceptions that can be thrown in the process of body generation.
     */
    public function __construct(
        private array $exceptionMap,
        private ResponseFactoryInterface $responseFactory,
        private Injector $injector,
        private bool $checkResponseBody = false,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $response = $handler->handle($request);
            if ($this->checkResponseBody) {
                $response->getBody();
            }
        } catch (Throwable $t) {
            foreach ($this->exceptionMap as $exceptionType => $responseHandler) {
                if ($t instanceof $exceptionType) {
                    if (is_int($responseHandler)) {
                        return $this->responseFactory->createResponse($responseHandler);
                    }

                    if (is_callable($responseHandler)) {
                        /** @var ResponseInterface */
                        return $this->injector->invoke($responseHandler, ['exception' => $t]);
                    }
                }
            }
            throw $t;
        }

        return $response;
    }
}
