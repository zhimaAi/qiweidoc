<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Libraries\Core\Http;

use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\DataResponse\Formatter\HtmlDataResponseFormatter;
use Yiisoft\DataResponse\Formatter\PlainTextDataResponseFormatter;
use Yiisoft\Http\Status;

class BaseController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private htmlDataResponseFormatter $htmlDataResponseFormatter,
        private PlainTextDataResponseFormatter $plainTextDataResponseFormattrer
    ) {
    }

    protected function jsonResponse(mixed $data = [], int $code = Status::OK)
    {
        return $this->responseFactory->createResponse($data, $code);
    }

    protected function htmlResponse(string $html = "", int $code = Status::OK)
    {
        return $this->responseFactory
            ->createResponse($html, $code)
            ->withResponseFormatter($this->htmlDataResponseFormatter);
    }

    protected function textResponse(string $content = "", int $code = Status::OK)
    {
        return $this->responseFactory
            ->createResponse($content, $code)
            ->withResponseFormatter($this->plainTextDataResponseFormattrer);
    }
}
