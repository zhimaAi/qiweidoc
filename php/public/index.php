<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

declare(strict_types=1);

use App\Libraries\Core\Yii;
use Yiisoft\ErrorHandler\ErrorHandler;
use Yiisoft\ErrorHandler\Renderer\JsonRenderer;
use Yiisoft\Log\Logger;
use Yiisoft\Log\Target\File\FileTarget;
use Yiisoft\Yii\Runner\Http\HttpApplicationRunner;

/**
 * @psalm-var string $_SERVER['REQUEST_URI']
 */

// PHP built-in server routing.
if (PHP_SAPI === 'cli-server') {
    // Serve static files as is.
    /** @psalm-suppress MixedArgument */
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file(__DIR__ . $path)) {
        return false;
    }

    // Explicitly set for URLs with dot.
    $_SERVER['SCRIPT_NAME'] = '/index.php';
}

require_once dirname(__DIR__) . '/autoload.php';

if (getenv('YII_C3')) {
    $c3 = dirname(__DIR__) . '/c3.php';
    if (file_exists($c3)) {
        require_once $c3;
    }
}

/**
 * Run HTTP application runner
 *
 * @psalm-suppress RedundantCast
 */
$runner = (
    new HttpApplicationRunner(
        rootPath: dirname(__DIR__),
        debug: (bool) $_ENV['YII_DEBUG'],
        checkEvents: (bool) $_ENV['YII_DEBUG'],
        environment: $_ENV['YII_ENV']
    )
)
    ->withTemporaryErrorHandler(new ErrorHandler(
        new Logger([new FileTarget(dirname(__DIR__) . '/runtime/logs/app.log')]),
        new JsonRenderer(),
    ))
;

Yii::setConfig($runner->getConfig());
Yii::setContainer($runner->getContainer());

$runner->run();
