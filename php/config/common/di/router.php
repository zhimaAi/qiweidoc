<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

use Common\Controller\PublicController;
use Common\Middlewares\ExceptionMiddleware;
use Common\Module;
use Psr\Container\ContainerInterface;
use Yiisoft\Config\Config;
use Yiisoft\Request\Body\RequestBodyParser;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\Router\RouteCollectorInterface;

/** @var Config $config */

return [
    RouteCollectionInterface::class => static function (ContainerInterface $container, RouteCollectorInterface $collector) {

        $collector
            ->middleware(RequestBodyParser::class)
            ->middleware(ExceptionMiddleware::class)
            ->addGroup(
                Group::create()->routes(
                    Route::get('/ping')->action([PublicController::class, 'ping']),
                    Route::get('/info')->action([PublicController::class, 'info']),
                    Route::post('/broadcast')->action([PublicController::class, 'broadcast']),
                    Route::get('/')->action([PublicController::class, 'frontBuild']),
                    ...Module::getRouterProvider()->getHttpRouters()
                ),
            );

        return new RouteCollection($collector);
    },
];
