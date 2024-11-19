<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

declare(strict_types=1);

namespace App\Libraries\Core\Http;

use RuntimeException;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\Http\Status;

final class ApiResponseDataFactory
{
    public function createFromResponse(DataResponse $response): ApiResponseData
    {
        $data = $this->formatData($response->getData()) ?: [];
        if ($data !== null && ! is_array($data)) {
            throw new RuntimeException('The response data must be either null or an array');
        }

        if ($response->getStatusCode() !== Status::OK) {
            return $this
                ->createErrorResponse()
                ->setData($response->getData())
                ->setErrorCode($response->getStatusCode())
                ->setErrorMessage($this->getErrorMessage($response));
        }

        return $this->createSuccessResponse()->setData($data);
    }

    public function createSuccessResponse(): ApiResponseData
    {
        return $this
            ->createResponse()
            ->setStatus('success');
    }

    public function createErrorResponse(): ApiResponseData
    {
        return $this
            ->createResponse()
            ->setStatus('failed');
    }

    public function createResponse(): ApiResponseData
    {
        return new ApiResponseData();
    }

    private function getErrorMessage(DataResponse $response): string
    {
        return $response->getReasonPhrase() ?: "Unknown error";
    }


    private function formatData($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'formatData'], $data);
        } elseif (is_object($data) && method_exists($data, 'toArray')) {
            return $data->toArray();
        }

        return $data;
    }
}
