<?php

declare(strict_types=1);

namespace Yiisoft\Router;

interface RouteCollectorInterface
{
    /**
     * Add a route.
     */
    public function addRoute(Route ...$route): self;

    /**
     * Add a group of routes.
     *
     * ```php
     * $group = Group::create('/api')
     * ->middleware($myMiddleware)
     * ->routes(
     *     Route::get('/users', function () {}),
     *     Route::get('/contacts', function () {}),
     * );
     * $routeCollector->addGroup($group);
     * ```
     *
     * @param Group ...$group A group to add.
     */
    public function addGroup(Group ...$group): self;

    /**
     * Appends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed first.
     */
    public function middleware(array|callable|string ...$middlewareDefinition): self;

    /**
     * Prepends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed last.
     */
    public function prependMiddleware(array|callable|string ...$middlewareDefinition): self;

    /**
     * @return Group[]|Route[]
     */
    public function getItems(): array;

    /**
     * @return array[]|callable[]|string[]
     */
    public function getMiddlewareDefinitions(): array;
}
