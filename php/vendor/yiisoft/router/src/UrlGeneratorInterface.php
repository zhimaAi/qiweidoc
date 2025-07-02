<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Stringable;

/**
 * `UrlGeneratorInterface` allows generating URL given route name, arguments, and query parameters.
 */
interface UrlGeneratorInterface
{
    /**
     * Generates URL from named route, arguments, and query parameters.
     *
     * @param string $name Name of the route.
     * @param array<string,scalar|Stringable|null> $arguments Argument-value set.
     * @param array $queryParameters Parameter-value set.
     *
     * @throws RouteNotFoundException In case there is no route with the name specified.
     *
     * @return string URL generated.
     */
    public function generate(string $name, array $arguments = [], array $queryParameters = []): string;

    /**
     * Generates absolute URL from named route, arguments, and query parameters.
     *
     * @param string $name Name of the route.
     * @param array<string,scalar|Stringable|null> $arguments Argument-value set.
     * @param array $queryParameters Parameter-value set.
     * @param string|null $scheme Host scheme.
     * @param string|null $host Host for manual setup.
     *
     * @throws RouteNotFoundException In case there is no route with the name specified.
     *
     * @return string URL generated.
     */
    public function generateAbsolute(
        string $name,
        array $arguments = [],
        array $queryParameters = [],
        string $scheme = null,
        string $host = null
    ): string;

    /**
     * Generate URL from the current route replacing some of its arguments with values specified.
     *
     * @param array<string,scalar|Stringable|null> $replacedArguments New argument values indexed by replaced argument
     * names.
     * @param array $queryParameters Parameter-value set.
     * @param string|null $fallbackRouteName Name of a route that should be used if current route.
     * can not be determined.
     */
    public function generateFromCurrent(
        array $replacedArguments,
        array $queryParameters = [],
        ?string $fallbackRouteName = null
    ): string;

    public function getUriPrefix(): string;

    public function setUriPrefix(string $name): void;

    /**
     * Set default argument value.
     *
     * @param string $name Name of argument to provide default value for.
     * @param bool|float|int|string|Stringable|null $value Default value.
     */
    public function setDefaultArgument(string $name, bool|float|int|string|Stringable|null $value): void;
}
