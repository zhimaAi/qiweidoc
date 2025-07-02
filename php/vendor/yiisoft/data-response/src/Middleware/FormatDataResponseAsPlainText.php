<?php

declare(strict_types=1);

namespace Yiisoft\DataResponse\Middleware;

use Yiisoft\DataResponse\Formatter\PlainTextDataResponseFormatter;

/**
 * `FormatDataResponseAsPlainText` adds a plain text formatter {@see PlainTextDataResponseFormatter} instance to the
 * instance of the data response {@see DataResponse}, if the formatter was not added earlier.
 */
final class FormatDataResponseAsPlainText extends FormatDataResponse
{
    public function __construct(PlainTextDataResponseFormatter $responseFormatter)
    {
        parent::__construct($responseFormatter);
    }
}
