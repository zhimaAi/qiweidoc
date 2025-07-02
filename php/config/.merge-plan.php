<?php

declare(strict_types=1);

// Do not edit. Content will be replaced.
return [
    '/' => [
        'params' => [
            'yiisoft/auth' => [
                'config/params.php',
            ],
            'yiisoft/cache-file' => [
                'config/params.php',
            ],
            'yiisoft/data-response' => [
                'config/params.php',
            ],
            'yiisoft/db-migration' => [
                'config/params.php',
            ],
            'yiisoft/router-fastroute' => [
                'config/params.php',
            ],
            'yiisoft/yii-debug' => [
                'config/params.php',
            ],
            'yiisoft/yii-runner-roadrunner' => [
                'config/params.php',
            ],
            'yiisoft/db' => [
                'config/params.php',
            ],
            'yiisoft/validator' => [
                'config/params.php',
            ],
            'yiisoft/log-target-file' => [
                'config/params.php',
            ],
            'yiisoft/profiler' => [
                'config/params.php',
            ],
            'yiisoft/aliases' => [
                'config/params.php',
            ],
            'yiisoft/session' => [
                'config/params.php',
            ],
            'yiisoft/translator' => [
                'config/params.php',
            ],
            '/' => [
                'common/params.php',
            ],
        ],
        'di' => [
            'yiisoft/cache' => [
                'config/di.php',
            ],
            'yiisoft/cache-file' => [
                'config/di.php',
            ],
            'yiisoft/router-fastroute' => [
                'config/di.php',
            ],
            'yiisoft/yii-debug' => [
                'config/di.php',
            ],
            'yiisoft/hydrator' => [
                'config/di.php',
            ],
            'yiisoft/validator' => [
                'config/di.php',
            ],
            'yiisoft/log-target-file' => [
                'config/di.php',
            ],
            'yiisoft/router' => [
                'config/di.php',
            ],
            'yiisoft/profiler' => [
                'config/di.php',
            ],
            'yiisoft/aliases' => [
                'config/di.php',
            ],
            'yiisoft/translator' => [
                'config/di.php',
            ],
            'yiisoft/yii-event' => [
                'config/di.php',
            ],
            '/' => [
                'common/di/*.php',
            ],
        ],
        'di-web' => [
            'yiisoft/data-response' => [
                'config/di-web.php',
            ],
            'yiisoft/input-http' => [
                'config/di-web.php',
            ],
            'yiisoft/router-fastroute' => [
                'config/di-web.php',
            ],
            'yiisoft/yii-debug' => [
                'config/di-web.php',
            ],
            'yiisoft/error-handler' => [
                'config/di-web.php',
            ],
            'yiisoft/request-provider' => [
                'config/di-web.php',
            ],
            'yiisoft/session' => [
                'config/di-web.php',
            ],
            'yiisoft/yii-event' => [
                'config/di-web.php',
            ],
            '/' => [
                '$di',
                'web/di/*.php',
            ],
        ],
        'di-console' => [
            'yiisoft/db-migration' => [
                'config/di-console.php',
            ],
            'yiisoft/yii-debug' => [
                'config/di-console.php',
            ],
            'yiisoft/yii-console' => [
                'config/di-console.php',
            ],
            'yiisoft/yii-event' => [
                'config/di-console.php',
            ],
            '/' => [
                '$di',
            ],
        ],
        'params-web' => [
            'yiisoft/input-http' => [
                'config/params-web.php',
            ],
            'yiisoft/yii-event' => [
                'config/params-web.php',
            ],
            '/' => [
                '$params',
                'web/params.php',
            ],
        ],
        'bootstrap' => [
            'yiisoft/yii-debug' => [
                'config/bootstrap.php',
            ],
            '/' => [],
        ],
        'events-web' => [
            'yiisoft/yii-debug' => [
                'config/events-web.php',
            ],
            'yiisoft/request-provider' => [
                'config/events-web.php',
            ],
            'yiisoft/log' => [
                'config/events-web.php',
            ],
            'yiisoft/profiler' => [
                'config/events-web.php',
            ],
            '/' => [
                '$events',
                'web/events.php',
            ],
        ],
        'di-providers' => [
            'yiisoft/yii-debug' => [
                'config/di-providers.php',
            ],
            '/' => [],
        ],
        'events-console' => [
            'yiisoft/yii-debug' => [
                'config/events-console.php',
            ],
            'yiisoft/log' => [
                'config/events-console.php',
            ],
            'yiisoft/yii-console' => [
                'config/events-console.php',
            ],
            '/' => [
                '$events',
            ],
        ],
        'params-console' => [
            'yiisoft/yii-console' => [
                'config/params-console.php',
            ],
            'yiisoft/yii-event' => [
                'config/params-console.php',
            ],
            '/' => [
                '$params',
                'console/params.php',
            ],
        ],
        'di-delegates' => [
            '/' => [],
        ],
        'di-delegates-console' => [
            '/' => [
                '$di-delegates',
            ],
        ],
        'di-delegates-web' => [
            '/' => [
                '$di-delegates',
            ],
        ],
        'di-providers-console' => [
            '/' => [
                '$di-providers',
            ],
        ],
        'di-providers-web' => [
            '/' => [
                '$di-providers',
            ],
        ],
        'events' => [
            '/' => [],
        ],
        'bootstrap-console' => [
            '/' => [
                '$bootstrap',
            ],
        ],
        'bootstrap-web' => [
            '/' => [
                '$bootstrap',
            ],
        ],
    ],
    'dev' => [
        'params' => [
            '/' => [
                'environments/dev/params.php',
            ],
        ],
    ],
    'prod' => [
        'params' => [
            '/' => [
                'environments/prod/params.php',
            ],
        ],
    ],
    'test' => [
        'params' => [
            '/' => [
                'environments/test/params.php',
            ],
        ],
    ],
];
