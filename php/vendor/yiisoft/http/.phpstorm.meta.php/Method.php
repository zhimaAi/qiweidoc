<?php

namespace PHPSTORM_META {

    expectedReturnValues(
        \Psr\Http\Message\RequestInterface::getMethod(),
        argumentsSet('\Yiisoft\Http\Method::METHODS'),
    );

    expectedArguments(
        \Psr\Http\Message\RequestInterface::withMethod(),
        0,
        argumentsSet('\Yiisoft\Http\Method::METHODS'),
    );

    expectedArguments(
        \Psr\Http\Message\RequestFactoryInterface::createRequest(),
        0,
        argumentsSet('\Yiisoft\Http\Method::METHODS'),
    );

    expectedArguments(
        \Psr\Http\Message\ServerRequestFactoryInterface::createServerRequest(),
        0,
        argumentsSet('\Yiisoft\Http\Method::METHODS'),
    );

    registerArgumentsSet(
        '\Yiisoft\Http\Method::METHODS',
        \Yiisoft\Http\Method::GET,
        \Yiisoft\Http\Method::POST,
        \Yiisoft\Http\Method::PUT,
        \Yiisoft\Http\Method::DELETE,
        \Yiisoft\Http\Method::PATCH,
        \Yiisoft\Http\Method::HEAD,
        \Yiisoft\Http\Method::OPTIONS,
    );
}
