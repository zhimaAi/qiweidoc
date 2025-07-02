<?php

namespace PHPSTORM_META {

    expectedArguments(
        \Yiisoft\Cookies\Cookie::withSameSite(),
        0,
        argumentsSet('\Yiisoft\Cookies\Cookie::SAME_SITE'),
    );

    registerArgumentsSet(
        '\Yiisoft\Cookies\Cookie::SAME_SITE',
        \Yiisoft\Cookies\Cookie::SAME_SITE_LAX,
        \Yiisoft\Cookies\Cookie::SAME_SITE_STRICT,
        \Yiisoft\Cookies\Cookie::SAME_SITE_NONE,
    );
}
