<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Yiisoft\Http\Method;

use function array_key_exists;
use function in_array;
use function is_array;

/**
 * @psalm-type Items = array<array-key,array|string>
 */
final class RouteCollection implements RouteCollectionInterface
{
    /**
     * @psalm-var Items
     */
    private array $items = [];

    /**
     * All attached routes as Route instances.
     *
     * @var Route[]
     */
    private array $routes = [];

    public function __construct(private RouteCollectorInterface $collector)
    {
    }

    public function getRoutes(): array
    {
        $this->ensureItemsInjected();
        return $this->routes;
    }

    public function getRoute(string $name): Route
    {
        $this->ensureItemsInjected();
        if (!array_key_exists($name, $this->routes)) {
            throw new RouteNotFoundException($name);
        }

        return $this->routes[$name];
    }

    public function getRouteTree(bool $routeAsString = true): array
    {
        $this->ensureItemsInjected();
        return $this->buildTree($this->items, $routeAsString);
    }

    private function ensureItemsInjected(): void
    {
        if ($this->items === []) {
            $this->injectItems($this->collector->getItems());
        }
    }

    /**
     * Build routes array.
     *
     * @param Group[]|Route[] $items
     */
    private function injectItems(array $items): void
    {
        foreach ($items as $item) {
            if (!$this->isStaticRoute($item)) {
                $item = $item->prependMiddleware(...$this->collector->getMiddlewareDefinitions());
            }
            $this->injectItem($item);
        }
    }

    /**
     * Add an item into routes array.
     */
    private function injectItem(Group|Route $route): void
    {
        if ($route instanceof Group) {
            $this->injectGroup($route, $this->items);
            return;
        }

        $routeName = $route->getData('name');
        $this->items[] = $routeName;
        if (isset($this->routes[$routeName]) && !$route->getData('override')) {
            throw new InvalidArgumentException("A route with name '$routeName' already exists.");
        }
        $this->routes[$routeName] = $route;
    }

    /**
     * Inject a Group instance into route and item arrays.
     *
     * @psalm-param Items $tree
     */
    private function injectGroup(Group $group, array &$tree, string $prefix = '', string $namePrefix = ''): void
    {
        $prefix .= (string) $group->getData('prefix');
        $namePrefix .= (string) $group->getData('namePrefix');
        $items = $group->getData('items');
        $pattern = null;
        $hosts = [];
        foreach ($items as $item) {
            if (!$this->isStaticRoute($item)) {
                $item = $item->prependMiddleware(...$group->getData('middlewareDefinitions'));
            }

            if (!empty($group->getData('hosts')) && empty($item->getData('hosts'))) {
                $item = $item->hosts(...$group->getData('hosts'));
            }

            if ($item instanceof Group) {
                if ($group->getData('hasCorsMiddleware')) {
                    $item = $item->withCors($group->getData('corsMiddleware'));
                }
                if (empty($item->getData('prefix'))) {
                    $this->injectGroup($item, $tree, $prefix, $namePrefix);
                    continue;
                }
                /** @psalm-suppress PossiblyNullArrayOffset Checked group prefix on not empty above. */
                if (!isset($tree[$item->getData('prefix')])) {
                    $tree[$item->getData('prefix')] = [];
                }
                /**
                 * @psalm-suppress MixedArgumentTypeCoercion
                 * @psalm-suppress MixedArgument,PossiblyNullArrayOffset
                 * Checked group prefix on not empty above.
                 */
                $this->injectGroup($item, $tree[$item->getData('prefix')], $prefix, $namePrefix);
                continue;
            }

            $modifiedItem = $item->pattern($prefix . $item->getData('pattern'));

            if (!str_contains($modifiedItem->getData('name'), implode(', ', $modifiedItem->getData('methods')))) {
                $modifiedItem = $modifiedItem->name($namePrefix . $modifiedItem->getData('name'));
            }

            if ($group->getData('hasCorsMiddleware')) {
                $this->processCors($group, $hosts, $pattern, $modifiedItem, $tree);
            }

            $routeName = $modifiedItem->getData('name');
            $tree[] = $routeName;
            if (isset($this->routes[$routeName]) && !$modifiedItem->getData('override')) {
                throw new InvalidArgumentException("A route with name '$routeName' already exists.");
            }
            $this->routes[$routeName] = $modifiedItem;
        }
    }

    /**
     * @psalm-param Items $tree
     */
    private function processCors(
        Group $group,
        array &$hosts,
        ?string &$pattern,
        Route &$modifiedItem,
        array &$tree
    ): void {
        /** @var array|callable|string $middleware */
        $middleware = $group->getData('corsMiddleware');
        $isNotDuplicate = !in_array(Method::OPTIONS, $modifiedItem->getData('methods'), true)
            && ($pattern !== $modifiedItem->getData('pattern') || $hosts !== $modifiedItem->getData('hosts'));

        $pattern = $modifiedItem->getData('pattern');
        $hosts = $modifiedItem->getData('hosts');
        $optionsRoute = Route::options($pattern);
        if (!empty($hosts)) {
            $optionsRoute = $optionsRoute->hosts(...$hosts);
        }
        if ($isNotDuplicate) {
            $optionsRoute = $optionsRoute->middleware($middleware);

            $routeName = $optionsRoute->getData('name');
            $tree[] = $routeName;
            $this->routes[$routeName] = $optionsRoute->action(
                static fn (ResponseFactoryInterface $responseFactory) => $responseFactory->createResponse(204)
            );
        }
        $modifiedItem = $modifiedItem->prependMiddleware($middleware);
    }

    /**
     * Builds route tree from items.
     *
     * @psalm-param Items $items
     */
    private function buildTree(array $items, bool $routeAsString): array
    {
        $tree = [];
        foreach ($items as $key => $item) {
            if (is_array($item)) {
                /** @psalm-var Items $item */
                $tree[$key] = $this->buildTree($item, $routeAsString);
            } else {
                $tree[] = $routeAsString ? (string) $this->getRoute($item) : $this->getRoute($item);
            }
        }
        return $tree;
    }

    private function isStaticRoute(Group|Route $item): bool
    {
        return $item instanceof Route && !$item->getData('hasMiddlewares');
    }
}
