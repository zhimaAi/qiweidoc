<?php

declare(strict_types=1);

use Yiisoft\Auth\AuthenticationMethodInterface;
use Yiisoft\Auth\Debug\AuthenticationMethodInterfaceProxy;
use Yiisoft\Auth\Debug\IdentityCollector;

return [
    'yiisoft/yii-debug' => [
        'collectors.web' => [
            IdentityCollector::class,
        ],
        'trackedServices' => [
            AuthenticationMethodInterface::class => [AuthenticationMethodInterfaceProxy::class, IdentityCollector::class],
        ],
    ],
];
