<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

namespace Common\Middlewares;

use Common\Exceptions\ValidationException;
use Common\Yii;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\DataResponse\Formatter\JsonDataResponseFormatter;
use Yiisoft\Http\Status;
use Yiisoft\Input\Http\InputValidationException;

final class ExceptionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly DataResponseFactoryInterface $dataResponseFactory,
        protected JsonDataResponseFormatter           $jsonDataResponseFormatter,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $logger = Yii::logger();
        $context = [
            'uri' => $request->getUri(),
            'method' => $request->getMethod(),
            'params' => $request->getQueryParams(),
            'body' => $request->getParsedBody(),
            'attributes' => $request->getAttributes(),
        ];

        try {
            return $handler->handle($request);
        } catch (InputValidationException | ValidationException $e) {
            $logger->warning($e, $context);
            $debugData = ['validation' => $e->getResult()->getErrorMessages()];
            return $this->response($e, $context, Status::BAD_REQUEST, '', $debugData);
        } catch (LogicException $e) { //逻辑错误
            $logger->warning($e, $context);
            return $this->response($e, $context, Status::UNPROCESSABLE_ENTITY);
        } catch (Throwable $e) { //其它错误
            $logger->error($e, $context);
            $message = "";
            if (!Yii::isDebug()) {
                $message = "服务器内部错误";
            }
            return $this->response($e, $context, Status::INTERNAL_SERVER_ERROR, $message);
        }
    }

    private function response(Throwable $e, array $context, int $code, string $reason = "", array $debugData = []): ResponseInterface
    {
        if (Yii::isDebug()) {
            $data = ['detail' => array_merge($context, ['line' => $e->getLine(), 'file' => $e->getFile(), 'trace' => $this->formatTrace($e)], $debugData)];
        } else {
            $data = [];
        }
        return $this->dataResponseFactory
            ->createResponse([
                'status' => 'failed',
                'error_message' => $reason ?: $e->getMessage(),
                'error_code' => $e->getCode() > 0 ? $e->getCode() : null,
                'data' => $this->toArray($data) ?: [],
            ], $code, $reason ?: $e->getMessage())
            ->withResponseFormatter($this->jsonDataResponseFormatter);
    }

    private function formatTrace(Throwable $e): array
    {
        $trace = [];
        foreach ($e->getTrace() as $item) {
            $trace[] = array_filter([
                'file' => $item['file'] ?? null,
                'line' => $item['line'] ?? null,
                'function' => $item['function'] ?? null,
                'class' => $item['class'] ?? null,
            ]);
        }

        // 只返回前10条trace记录
        return array_slice($trace, 0, 10);
    }

    private function toArray(mixed $data)
    {
        if (is_array($data)) {
            return array_map([$this, 'toArray'], $data);
        } elseif (is_object($data) && method_exists($data, 'toArray')) {
            return $data->toArray();
        }

        return $data;
    }
}
