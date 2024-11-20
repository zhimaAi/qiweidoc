<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

declare(strict_types=1);

namespace App\Libraries\Core\Http;

use App\Libraries\Core\Exception\LockAcquisitionException;
use App\Libraries\Core\Exception\UnauthorizedException;
use App\Libraries\Core\Exception\ValidationException;
use App\Libraries\Core\Exception\WechatRequestException;
use App\Libraries\Core\Yii;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Status;
use Yiisoft\Input\Http\InputValidationException;

final class ExceptionMiddleware implements MiddlewareInterface
{
    public function __construct(private DataResponseFactoryInterface $dataResponseFactory)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $logger = Yii::logger('exception');
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
        } catch (UnauthorizedException $e) { //登录过期
            return $this->response($e, $context, Status::UNAUTHORIZED);
        } catch (LockAcquisitionException $e) {  //获取锁失败
            $logger->warning($e, $context);
            $message = "";
            if (! Yii::isDebug()) {
                $message = "操作失败";
            }

            return $this->response($e, $context, Status::LOCKED, $message);
        } catch (LogicException $e) { //逻辑错误
            return $this->response($e, $context, Status::UNPROCESSABLE_ENTITY);
        } catch (WechatRequestException $e) { //企微接口请求错误
            $logger->warning($e, $context);
            if ($e->getCode() == WechatRequestException::STATIC_ERROR) {
                $message = "企微接口请求异常";
            } elseif ($e->getCode() == WechatRequestException::STATIC_FAIL) {
                $message = "企微接口请求失败";
            } else {
                $message = "企微接口请求出错";
            }

            return $this->response($e, $context, Status::INTERNAL_SERVER_ERROR, $message);
        } catch (Throwable $e) { //其它错误
            $logger->error($e, $context);
            $message = "";
            if (! Yii::isDebug()) {
                $message = "服务器内部错误";
            }

            return $this->response($e, $context, Status::INTERNAL_SERVER_ERROR, $message);
        }
    }

    private function response(Throwable $e, array $context, int $code, string $reason = "", array $debugData = []): ResponseInterface
    {
        if (Yii::isDebug()) {
            $data = ['detail' => array_merge($context, ['trace' => $this->formatTrace($e)], $debugData)];
        } else {
            $data = [];
        }

        return $this->dataResponseFactory->createResponse($data, $code, $reason ?: $e->getMessage());
    }

    private function formatTrace(Throwable $e): array
    {
        $trace = [];
        foreach ($e->getTrace() as $item) {
            // 只保留关键信息
            $trace[] = array_filter([
                'file' => $item['file'] ?? null,
                'line' => $item['line'] ?? null,
                'function' => $item['function'] ?? null,
                'class' => $item['class'] ?? null,
            ]);
        }

        // 只返回前10条记录
        return array_slice($trace, 0, 10);
    }
}
