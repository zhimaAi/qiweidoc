<?php

declare(strict_types=1);

namespace Yiisoft\DataResponse;

use Yiisoft\Http\Status;

/**
 * `DataResponseFactoryInterface` is the interface that should be implemented by data response factory classes.
 */
interface DataResponseFactoryInterface
{
    /**
     * Creates an instance of the data response.
     *
     * @param mixed $data The response data.
     * @param int $code The response status code.
     * @param string $reasonPhrase The response reason phrase associated with the status code.
     *
     * @return DataResponse The instance of the data response.
     */
    public function createResponse($data = null, int $code = Status::OK, string $reasonPhrase = ''): DataResponse;
}
