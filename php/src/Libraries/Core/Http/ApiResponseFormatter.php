<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

declare(strict_types=1);

namespace App\Libraries\Core\Http;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFormatterInterface;
use Yiisoft\DataResponse\Formatter\JsonDataResponseFormatter;

final class ApiResponseFormatter implements DataResponseFormatterInterface
{
    public function __construct(private ApiResponseDataFactory $apiResponseDataFactory, private JsonDataResponseFormatter $jsonDataResponseFormatter)
    {
    }

    public function format(DataResponse $dataResponse): ResponseInterface
    {
        $response = $dataResponse->withData(
            $this->apiResponseDataFactory
                ->createFromResponse($dataResponse)
                ->toArray(),
        );

        return $this->jsonDataResponseFormatter->format($response);
    }
}
