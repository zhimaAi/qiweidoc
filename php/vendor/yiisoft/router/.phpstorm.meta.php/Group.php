<?php

namespace PHPSTORM_META {

    expectedArguments(\Yiisoft\Router\Group::getData(), 0, argumentsSet('groupDataKeys'));

    registerArgumentsSet(
        'groupDataKeys',
        'prefix',
        'namePrefix',
        'host',
        'hosts',
        'corsMiddleware',
        'items',
        'middlewareDefinitions',
        'hasDispatcher',
        'hasCorsMiddleware'
    );
}