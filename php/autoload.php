<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

declare(strict_types=1);

use Dotenv\Dotenv;

require_once __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createMutable(__DIR__ );
    $dotenv->load();
}

$_ENV['YII_ENV'] = empty($_ENV['APP_ENV']) ? 'dev' : $_ENV['APP_ENV'];
$_SERVER['YII_ENV'] = $_ENV['YII_ENV'];

$_ENV['YII_DEBUG'] = filter_var(
    !empty($_ENV['APP_DEBUG']) ? $_ENV['APP_DEBUG'] : true,
    FILTER_VALIDATE_BOOLEAN,
    FILTER_NULL_ON_FAILURE
) ?? true;
$_SERVER['YII_DEBUG'] = $_ENV['YII_DEBUG'];

date_default_timezone_set('Asia/Shanghai');
ini_set('memory_limit', '256M');

$_ENV['DB_HOST'] = $_ENV['DB_HOST'] ?? 'db';
$_ENV['DB_PORT'] = $_ENV['DB_PORT'] ?? 5432;
$_ENV['DB_DATABASE'] = $_ENV['DB_DATABASE'] ?? 'postgres';
$_ENV['DB_USERNAME'] = $_ENV['DB_USERNAME'] ?? 'postgres';
$_ENV['DB_PASSWORD'] = $_ENV['DB_PASSWORD'] ?? 'postgres';

$_ENV['REDIS_HOST'] = $_ENV['REDIS_HOST'] ?? 'redis';
$_ENV['REDIS_PORT'] = $_ENV['REDIS_PORT'] ?? 6379;
$_ENV['REDIS_PASSWORD'] = $_ENV['REDIS_PASSWORD'] ?? '';
