<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use LogicException;
use Psr\Http\Message\UriInterface;

/**
 * Holds information about current route e.g. matched last.
 */
final class CurrentRoute
{
    /**
     * Current Route.
     */
    private ?Route $route = null;

    /**
     * Current URI.
     */
    private ?UriInterface $uri = null;

    /**
     * Current Route arguments.
     *
     * @var array<string, string>
     */
    private array $arguments = [];

    /**
     * Returns the current route name.
     *
     * @return string|null The current route name.
     */
    public function getName(): ?string
    {
        return $this->route?->getData('name');
    }

    /**
     * Returns the current route host.
     *
     * @return string|null The current route host.
     */
    public function getHost(): ?string
    {
        return $this->route?->getData('host');
    }

    /**
     * Returns the current route pattern.
     *
     * @return string|null The current route pattern.
     */
    public function getPattern(): ?string
    {
        return $this->route?->getData('pattern');
    }

    /**
     * Returns the current route methods.
     *
     * @return string[]|null The current route methods.
     */
    public function getMethods(): ?array
    {
        return $this->route?->getData('methods');
    }

    /**
     * Returns the current URI.
     *
     * @return UriInterface|null The current URI.
     */
    public function getUri(): ?UriInterface
    {
        return $this->uri;
    }

    /**
     * @param array<string,string> $arguments
     *
     * @internal
     */
    public function setRouteWithArguments(Route $route, array $arguments): void
    {
        if ($this->route === null && $this->arguments === []) {
            $this->route = $route;
            $this->arguments = $arguments;
            return;
        }
        throw new LogicException('Can not set route/arguments since it was already set.');
    }

    /**
     * @internal
     */
    public function setUri(UriInterface $uri): void
    {
        if ($this->uri === null) {
            $this->uri = $uri;
            return;
        }
        throw new LogicException('Can not set URI since it was already set.');
    }

    /**
     * @return array<string, string> Arguments.
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getArgument(string $name, ?string $default = null): ?string
    {
        return $this->arguments[$name] ?? $default;
    }
}
