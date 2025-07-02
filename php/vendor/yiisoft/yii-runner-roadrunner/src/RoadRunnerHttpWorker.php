<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\RoadRunner;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunner\Http\PSR7WorkerInterface;
use Spiral\RoadRunner\Worker;
use Throwable;
use Yiisoft\ErrorHandler\ErrorHandler;
use Yiisoft\ErrorHandler\Middleware\ErrorCatcher;
use Yiisoft\Http\Status;
use Yiisoft\Yii\Http\Handler\ThrowableHandler;

use function microtime;

/**
 * @internal
 */
final class RoadRunnerHttpWorker
{
    private ResponseFactoryInterface $responseFactory;
    private PSR7WorkerInterface $worker;
    private ErrorCatcher $errorCatcher;
    private ErrorHandler $errorHandler;

    public function __construct(ContainerInterface $container, PSR7WorkerInterface $worker = null)
    {
        /** @psalm-var ResponseFactoryInterface $this->responseFactory */
        $this->responseFactory = $container->get(ResponseFactoryInterface::class);
        /** @psalm-var ErrorCatcher $this->errorCatcher */
        $this->errorCatcher = $container->get(ErrorCatcher::class);
        /** @psalm-var ErrorHandler $this->errorHandler */
        $this->errorHandler = $container->get(ErrorHandler::class);
        /** @psalm-suppress MixedArgument */
        $this->worker = $worker ?? new PSR7Worker(
            Worker::create(),
            $container->get(ServerRequestFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(UploadedFileFactoryInterface::class),
        );
    }

    public function respond(ResponseInterface $response): void
    {
        $this->worker->respond($response);
    }

    public function respondWithError(Throwable $throwable, ServerRequestInterface $request = null): ResponseInterface
    {
        if ($request === null) {
            $errorData = $this->errorHandler->handle($throwable);
            $response = $errorData->addToResponse($this->responseFactory->createResponse(Status::BAD_REQUEST));
        } else {
            $response = $this->errorCatcher->process($request, new ThrowableHandler($throwable));
        }

        $this->respond($response);
        return $response;
    }

    public function waitRequest(): ServerRequestInterface|Throwable|null
    {
        try {
            return $this->worker->waitRequest()?->withAttribute('applicationStartTime', microtime(true));
        } catch (Throwable $t) {
            return $t;
        }
    }
}
