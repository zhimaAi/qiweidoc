<?php

declare(strict_types=1);

namespace Yiisoft\DataResponse;

use Psr\Http\Message\ResponseInterface;

/**
 * DataResponseFormatterInterface is the interface that should be implemented by data response formatters.
 */
interface DataResponseFormatterInterface
{
    /**
     * Returns an instance of the response with formatted response data.
     *
     * @param DataResponse $dataResponse The instance of the data response.
     *
     * @return ResponseInterface The response with formatted response data.
     */
    public function format(DataResponse $dataResponse): ResponseInterface;
}
