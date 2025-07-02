<?php

declare(strict_types=1);

namespace Yiisoft\DataResponse\Middleware;

use Yiisoft\DataResponse\Formatter\XmlDataResponseFormatter;

/**
 * FormatDataResponseAsXml adds an XML formatter {@see XmlDataResponseFormatter} instance to the
 * instance of the data response {@see DataResponse}, if the formatter was not added earlier.
 */
final class FormatDataResponseAsXml extends FormatDataResponse
{
    public function __construct(XmlDataResponseFormatter $responseFormatter)
    {
        parent::__construct($responseFormatter);
    }
}
