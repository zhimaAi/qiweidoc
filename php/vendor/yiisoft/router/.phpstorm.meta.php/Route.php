<?php

namespace PHPSTORM_META {

    expectedArguments(\Yiisoft\Router\Route::getData(), 0, argumentsSet('routeDataKeys'));

    registerArgumentsSet(
        'routeDataKeys',
        'name',
        'host',
        'hosts',
        'pattern',
        'methods',
        'override',
        'defaults',
        'dispatcherWithMiddlewares',
        'hasDispatcher',
        'hasMiddlewares'
    );
}