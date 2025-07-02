<?php

declare(strict_types=1);

namespace Yiisoft\DataResponse\Middleware;

use Yiisoft\DataResponse\Formatter\JsonDataResponseFormatter;

/**
 * FormatDataResponseAsJson adds a JSON formatter {@see JsonDataResponseFormatter} instance to the
 * instance of the data response {@see DataResponse}, if the formatter was not added earlier.
 */
final class FormatDataResponseAsJson extends FormatDataResponse
{
    public function __construct(JsonDataResponseFormatter $responseFormatter)
    {
        parent::__construct($responseFormatter);
    }
}
