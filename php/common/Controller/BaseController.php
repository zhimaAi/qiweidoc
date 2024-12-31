<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Common\Controller;

use BackedEnum;
use Carbon\Carbon;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\DataResponse\Formatter\HtmlDataResponseFormatter;
use Yiisoft\DataResponse\Formatter\JsonDataResponseFormatter;
use Yiisoft\DataResponse\Formatter\PlainTextDataResponseFormatter;
use Yiisoft\DataResponse\Formatter\XmlDataResponseFormatter;
use Yiisoft\Http\Status;

class BaseController
{
    public function __construct(
        protected DataResponseFactoryInterface $responseFactory,
        protected JsonDataResponseFormatter $jsonDataResponseFormatter,
        protected htmlDataResponseFormatter $htmlDataResponseFormatter,
        protected PlainTextDataResponseFormatter $plainTextDataResponseFormatter,
        protected XmlDataResponseFormatter $xmlDataResponseFormatter,
    ) {
    }

    protected function jsonResponse(mixed $data = [], int $code = Status::OK, string $errMsg = ""): DataResponse
    {
        return $this->responseFactory
            ->createResponse([
                'status' => ($code >= 200 && $code < 300) ? 'success' : 'failed',
                'error_message' => $errMsg,
                'error_code' => ($code > 200 && $code < 300) ? null : $code,
                'data' => $this->toArray($data) ?: [],
            ], $code, $errMsg)
            ->withResponseFormatter($this->jsonDataResponseFormatter);
    }

    protected function htmlResponse(string $html = "", int $code = Status::OK, string $errMsg = ""): DataResponse
    {
        return $this->responseFactory
            ->createResponse($html, $code, $errMsg)
            ->withResponseFormatter($this->htmlDataResponseFormatter);
    }

    protected function textResponse(string $content = "", int $code = Status::OK, string $errMsg = ""): DataResponse
    {
        return $this->responseFactory
            ->createResponse($content, $code, $errMsg)
            ->withResponseFormatter($this->plainTextDataResponseFormatter);
    }

    protected function xmlResponse(string $content = "", int $code = Status::OK, string $errMsg = ""): DataResponse
    {
        return $this->responseFactory
            ->createResponse($content, $code, $errMsg)
            ->withResponseFormatter($this->xmlDataResponseFormatter);
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
