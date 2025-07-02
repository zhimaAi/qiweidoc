<?php

declare(strict_types=1);

namespace Yiisoft\Router\FastRoute;

use FastRoute\DataGenerator\GroupCountBased as RouteGenerator;
use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as RouteParser;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Yiisoft\Http\Method;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\Router\UrlMatcherInterface;

use function array_merge;

/**
 * @psalm-type ResultNotFound = array{0:0}
 * @psalm-type ResultMethodNotAllowed = array{0:2,1:string[]}
 * @psalm-type ResultFound = array{0:1,1:string,2:array<string,string>}
 *
 * @psalm-type DispatcherCallback=Closure(array):Dispatcher
 */
final class UrlMatcher implements UrlMatcherInterface
{
    /**
     * Configuration key used to set the cache file path.
     */
    public const CONFIG_CACHE_KEY = 'cache_key';

    /**
     * Configuration key used to set the cache file path.
     */
    private string $cacheKey = 'routes-cache';

    /**
     * @var ?callable A factory callback that can return a dispatcher.
     * @psalm-var DispatcherCallback|null
     */
    private $dispatcherCallback;

    /**
     * @var array Cached data used by the dispatcher.
     */
    private array $dispatchData = [];

    /**
     * @var bool Whether cache is enabled and valid dispatch data has been loaded from cache.
     */
    private bool $hasCache = false;

    private RouteCollector $fastRouteCollector;
    private bool $hasInjectedRoutes = false;

    /**
     * Accepts optionally a FastRoute RouteCollector and a callable factory
     * that can return a FastRoute dispatcher.
     *
     * If either is not provided defaults will be used:
     *
     * - A RouteCollector instance will be created composing a RouteParser and
     *   RouteGenerator.
     * - A callable that returns a GroupCountBased dispatcher will be created.
     *
     * @param RouteCollector|null $fastRouteCollector If not provided, a default implementation will be used.
     * @param callable|null $dispatcherFactory Callable that will return a FastRoute dispatcher.
     * @param array|null $config Array of custom configuration options.
     *
     * @psalm-param DispatcherCallback|null $dispatcherFactory
     */
    public function __construct(
        private RouteCollectionInterface $routeCollection,
        private ?CacheInterface $cache = null,
        ?array $config = null,
        ?RouteCollector $fastRouteCollector = null,
        ?callable $dispatcherFactory = null
    ) {
        $this->fastRouteCollector = $fastRouteCollector ?? $this->createRouteCollector();
        $this->dispatcherCallback = $dispatcherFactory;
        $this->loadConfig($config);

        $this->loadDispatchData();
    }

    public function match(ServerRequestInterface $request): MatchingResult
    {
        if (!$this->hasCache && !$this->hasInjectedRoutes) {
            $this->injectRoutes();
        }

        $dispatchData = $this->getDispatchData();
        $path = urldecode($request
            ->getUri()
            ->getPath());
        $method = $request->getMethod();

        /**
         * @psalm-var ResultNotFound|ResultMethodNotAllowed|ResultFound $result
         */
        $result = $this
            ->getDispatcher($dispatchData)
            ->dispatch($method, $request
                    ->getUri()
                    ->getHost() . $path);

        /** @psalm-suppress ArgumentTypeCoercion Psalm can't determine correct type here */
        return $result[0] !== Dispatcher::FOUND
            ? $this->marshalFailedRoute($result)
            : $this->marshalMatchedRoute($result);
    }

    /**
     * Load configuration parameters.
     *
     * @param array|null $config Array of custom configuration options.
     */
    private function loadConfig(?array $config): void
    {
        if ($config === null) {
            return;
        }

        if (isset($config[self::CONFIG_CACHE_KEY])) {
            $this->cacheKey = (string) $config[self::CONFIG_CACHE_KEY];
        }
    }

    /**
     * Retrieve the dispatcher instance.
     *
     * Uses the callable factory in $dispatcherCallback, passing it $data
     * (which should be derived from the router's getData() method); this
     * approach is done to allow testing against the dispatcher.
     *
     * @param array $data Data from {@see RouteCollector::getData()}.
     */
    private function getDispatcher(array $data): Dispatcher
    {
        if (!$this->dispatcherCallback) {
            $this->dispatcherCallback = $this->createDispatcherCallback();
        }

        $factory = $this->dispatcherCallback;

        return $factory($data);
    }

    /**
     * Create a default FastRoute Collector instance.
     */
    private function createRouteCollector(): RouteCollector
    {
        return new RouteCollector(new RouteParser(), new RouteGenerator());
    }

    /**
     * Returns a default implementation of a callback that can return a Dispatcher.
     *
     * @psalm-return DispatcherCallback
     */
    private function createDispatcherCallback(): callable
    {
        return static fn (array $data) => new GroupCountBased($data);
    }

    /**
     * Marshal a routing failure result.
     *
     * If the failure was due to the HTTP method, passes the allowed HTTP
     * methods to the factory.
     *
     * @psalm-param ResultNotFound|ResultMethodNotAllowed $result
     */
    private function marshalFailedRoute(array $result): MatchingResult
    {
        $resultCode = $result[0];
        if ($resultCode === Dispatcher::METHOD_NOT_ALLOWED) {
            /** @psalm-var ResultMethodNotAllowed $result */
            return MatchingResult::fromFailure($result[1]);
        }

        return MatchingResult::fromFailure(Method::ALL);
    }

    /**
     * Marshals a route result based on the results of matching.
     *
     * @psalm-param ResultFound $result
     */
    private function marshalMatchedRoute(array $result): MatchingResult
    {
        [, $name, $arguments] = $result;

        $route = $this->routeCollection->getRoute($name);

        if (isset($arguments['_host'])) {
            unset($arguments['_host']);
        }
        $arguments = array_merge($route->getData('defaults'), $arguments);

        return MatchingResult::fromSuccess($route, $arguments);
    }

    /**
     * Inject routes into the underlying router.
     */
    private function injectRoutes(): void
    {
        foreach ($this->routeCollection->getRoutes() as $route) {
            if (!$route->getData('hasMiddlewares')) {
                continue;
            }

            $hosts = $route->getData('hosts');
            $count = count($hosts);

            if ($count > 1) {
                $hosts = implode('|', $hosts);

                if (preg_match('~' . RouteParser::VARIABLE_REGEX . '~x', $hosts)) {
                    throw new RuntimeException('Placeholders are not allowed with multiple host names.');
                }

                $hostPattern = '{_host:' . $hosts . '}';
            } elseif ($count === 1) {
                $hostPattern = $hosts[0];
            } else {
                $hostPattern = '{_host:[a-zA-Z0-9\.\-]*}';
            }

            $this->fastRouteCollector->addRoute(
                $route->getData('methods'),
                $hostPattern . $route->getData('pattern'),
                $route->getData('name')
            );
        }
        $this->hasInjectedRoutes = true;
    }

    /**
     * Get the dispatch data either from cache or freshly generated by the
     * FastRoute data generator.
     *
     * If caching is enabled, store the freshly generated data to file.
     */
    private function getDispatchData(): array
    {
        if ($this->hasCache) {
            return $this->dispatchData;
        }

        $dispatchData = $this->fastRouteCollector->getData();

        if ($this->cache !== null) {
            $this->cacheDispatchData($dispatchData);
        }

        return $dispatchData;
    }

    /**
     * Load dispatch data from cache.
     */
    private function loadDispatchData(): void
    {
        if ($this->cache !== null && $this->cache->has($this->cacheKey)) {
            /** @var array $dispatchData */
            $dispatchData = $this->cache->get($this->cacheKey);

            $this->hasCache = true;
            $this->dispatchData = $dispatchData;
            return;
        }

        $this->hasCache = false;
    }

    /**
     * Save dispatch data to cache.
     *
     * @psalm-suppress PossiblyNullReference
     */
    private function cacheDispatchData(array $dispatchData): void
    {
        $this->cache->set($this->cacheKey, $dispatchData);
    }
}
