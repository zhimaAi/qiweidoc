<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

use Yiisoft\DataResponse\DataResponseFactory;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\DataResponse\Formatter\HtmlDataResponseFormatter;
use Yiisoft\DataResponse\Formatter\JsonDataResponseFormatter;
use Yiisoft\DataResponse\Formatter\XmlDataResponseFormatter;
use Yiisoft\DataResponse\Middleware\ContentNegotiator;

/* @var $params array */

return [
    DataResponseFactoryInterface::class => DataResponseFactory::class,
    ContentNegotiator::class => [
        '__construct()' => [
            'contentFormatters' => [
                'text/html' => new HtmlDataResponseFormatter(),
                'application/xml' => new XmlDataResponseFormatter(),
                'application/json' => new JsonDataResponseFormatter(),
            ],
        ],
    ],
];
