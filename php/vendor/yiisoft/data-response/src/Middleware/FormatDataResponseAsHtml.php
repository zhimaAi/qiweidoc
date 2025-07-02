<?php

declare(strict_types=1);

namespace Yiisoft\DataResponse\Middleware;

use Yiisoft\DataResponse\Formatter\HtmlDataResponseFormatter;

/**
 * FormatDataResponseAsHtml adds an HTML formatter {@see HtmlDataResponseFormatter} instance to the
 * instance of the data response {@see DataResponse}, if the formatter was not added earlier.
 */
final class FormatDataResponseAsHtml extends FormatDataResponse
{
    public function __construct(HtmlDataResponseFormatter $responseFormatter)
    {
        parent::__construct($responseFormatter);
    }
}
