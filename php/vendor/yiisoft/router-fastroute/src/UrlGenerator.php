<?php

declare(strict_types=1);

namespace Yiisoft\Router\FastRoute;

use FastRoute\RouteParser;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Stringable;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\Router\RouteNotFoundException;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;

use function array_key_exists;
use function array_keys;
use function implode;
use function is_string;
use function preg_match;

final class UrlGenerator implements UrlGeneratorInterface
{
    private string $uriPrefix = '';

    /**
     * @var array<string,string>
     */
    private array $defaultArguments = [];
    private bool $encodeRaw = true;
    private RouteParser $routeParser;

    public function __construct(
        private RouteCollectionInterface $routeCollection,
        private ?CurrentRoute $currentRoute = null,
        ?RouteParser $parser = null,
        private ?string $scheme = null,
        private ?string $host = null,
    ) {
        $this->routeParser = $parser ?? new RouteParser\Std();
    }

    /**
     * {@inheritDoc}
     *
     * Replacements in FastRoute are written as `{name}` or `{name:<pattern>}`;
     * this method uses {@see RouteParser\Std} to search for the best route
     * match based on the available substitutions and generates a URI.
     *
     * @throws RuntimeException If parameter value does not match its regex.
     */
    public function generate(string $name, array $arguments = [], array $queryParameters = []): string
    {
        $arguments = array_map('\strval', array_merge($this->defaultArguments, $arguments));
        $route = $this->routeCollection->getRoute($name);
        /** @var list<list<list<string>|string>> $parsedRoutes */
        $parsedRoutes = array_reverse($this->routeParser->parse($route->getData('pattern')));
        if ($parsedRoutes === []) {
            throw new RouteNotFoundException($name);
        }

        $missingArguments = [];

        // One route pattern can correspond to multiple routes if it has optional parts.
        foreach ($parsedRoutes as $parsedRouteParts) {
            // Check if all arguments can be substituted
            $missingArguments = $this->missingArguments($parsedRouteParts, $arguments);

            // If not all arguments can be substituted, try the next route.
            if (!empty($missingArguments)) {
                continue;
            }

            return $this->generatePath($arguments, $queryParameters, $parsedRouteParts);
        }

        // No valid route was found: list minimal required parameters.
        throw new RuntimeException(
            sprintf(
                'Route `%s` expects at least argument values for [%s], but received [%s]',
                $name,
                implode(',', $missingArguments),
                implode(',', array_keys($arguments))
            )
        );
    }

    public function generateAbsolute(
        string $name,
        array $arguments = [],
        array $queryParameters = [],
        ?string $scheme = null,
        ?string $host = null
    ): string {
        $url = $this->generate($name, $arguments, $queryParameters);
        $route = $this->routeCollection->getRoute($name);
        $uri = $this->currentRoute && $this->currentRoute->getUri() !== null ? $this->currentRoute->getUri() : null;
        $lastRequestScheme = $uri?->getScheme();

        $host ??= $route->getData('host') ?? $this->host ?? null;
        if ($host !== null) {
            $isRelativeHost = $this->isRelative($host);

            $scheme ??= $isRelativeHost
                ? $this->scheme ?? $lastRequestScheme
                : null;

            if ($scheme === null && !$isRelativeHost) {
                return rtrim($host, '/') . $url;
            }

            if ($host !== '' && $isRelativeHost) {
                $host = '//' . $host;
            }

            return $this->ensureScheme(rtrim($host, '/') . $url, $scheme);
        }

        return $uri === null ? $url : $this->generateAbsoluteFromLastMatchedRequest($url, $uri, $scheme);
    }

    /**
     * {@inheritdoc}
     */
    public function generateFromCurrent(
        array $replacedArguments,
        array $queryParameters = [],
        ?string $fallbackRouteName = null,
    ): string {
        if ($this->currentRoute === null || $this->currentRoute->getName() === null) {
            if ($fallbackRouteName !== null) {
                return $this->generate($fallbackRouteName, $replacedArguments);
            }

            if ($this->currentRoute !== null && $this->currentRoute->getUri() !== null) {
                return $this->currentRoute->getUri()->getPath();
            }

            throw new RuntimeException('Current route is not detected.');
        }

        if ($this->currentRoute->getUri() !== null) {
            $currentQueryParameters = [];
            parse_str($this->currentRoute->getUri()->getQuery(), $currentQueryParameters);
            $queryParameters = array_merge($currentQueryParameters, $queryParameters);
        }

        /** @psalm-suppress PossiblyNullArgument Checked route name on null above. */
        return $this->generate(
            $this->currentRoute->getName(),
            array_merge($this->currentRoute->getArguments(), $replacedArguments),
            $queryParameters,
        );
    }

    /**
     * @psalm-param null|object|scalar $value
     */
    public function setDefaultArgument(string $name, $value): void
    {
        if (!is_scalar($value) && !$value instanceof Stringable && $value !== null) {
            throw new InvalidArgumentException('Default should be either scalar value or an instance of \Stringable.');
        }
        $this->defaultArguments[$name] = (string) $value;
    }

    private function generateAbsoluteFromLastMatchedRequest(string $url, UriInterface $uri, ?string $scheme): string
    {
        $port = '';
        $uriPort = $uri->getPort();
        if ($uriPort !== 80 && $uriPort !== null) {
            $port = ':' . $uriPort;
        }

        return $this->ensureScheme('://' . $uri->getHost() . $port . $url, $scheme ?? $uri->getScheme());
    }

    /**
     * Normalize URL by ensuring that it use specified scheme.
     *
     * If URL is relative or scheme is null, normalization is skipped.
     *
     * @param string $url The URL to process.
     * @param string|null $scheme The URI scheme used in URL (e.g. `http` or `https`). Use empty string to
     * create protocol-relative URL (e.g. `//example.com/path`).
     *
     * @return string The processed URL.
     */
    private function ensureScheme(string $url, ?string $scheme): string
    {
        if ($scheme === null || $this->isRelative($url)) {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            // e.g. //example.com/path/to/resource
            return $scheme === '' ? $url : "$scheme:$url";
        }

        if (($pos = strpos($url, '://')) !== false) {
            if ($scheme === '') {
                $url = substr($url, $pos + 1);
            } else {
                $url = $scheme . substr($url, $pos);
            }
        }

        return $url;
    }

    /**
     * Returns a value indicating whether a URL is relative.
     * A relative URL does not have host info part.
     *
     * @param string $url The URL to be checked.
     *
     * @return bool Whether the URL is relative.
     */
    private function isRelative(string $url): bool
    {
        return strncmp($url, '//', 2) && !str_contains($url, '://');
    }

    public function getUriPrefix(): string
    {
        return $this->uriPrefix;
    }

    public function setEncodeRaw(bool $encodeRaw): void
    {
        $this->encodeRaw = $encodeRaw;
    }

    public function setUriPrefix(string $name): void
    {
        $this->uriPrefix = $name;
    }

    /**
     * Checks for any missing route parameters.
     *
     * @param list<list<string>|string> $parts
     *
     * @return string[] Either an array containing missing required parameters or an empty array if none are missing.
     */
    private function missingArguments(array $parts, array $substitutions): array
    {
        $missingArguments = [];

        // Gather required arguments.
        foreach ($parts as $part) {
            if (is_string($part)) {
                continue;
            }

            $missingArguments[] = $part[0];
        }

        // Check if all arguments exist.
        foreach ($missingArguments as $argument) {
            if (!array_key_exists($argument, $substitutions)) {
                // Return the arguments, so they can be used in an
                // exception if needed.
                return $missingArguments;
            }
        }

        // All required arguments are available.
        return [];
    }

    /**
     * @param array<string,string> $arguments
     * @param list<list<string>|string> $parts
     */
    private function generatePath(array $arguments, array $queryParameters, array $parts): string
    {
        $path = $this->getUriPrefix();

        foreach ($parts as $part) {
            if (is_string($part)) {
                // Append the string.
                $path .= $part;
                continue;
            }

            if ($arguments[$part[0]] !== '') {
                // Check substitute value with regex.
                $pattern = str_replace('~', '\~', $part[1]);
                if (preg_match('~^' . $pattern . '$~', $arguments[$part[0]]) === 0) {
                    throw new RuntimeException(
                        sprintf(
                            'Argument value for [%s] did not match the regex `%s`',
                            $part[0],
                            $part[1]
                        )
                    );
                }

                // Append the substituted value.
                $path .= $this->encodeRaw
                    ? rawurlencode($arguments[$part[0]])
                    : urlencode($arguments[$part[0]]);
            }
        }

        $path = str_replace('//', '/', $path);

        $queryString = '';
        if (!empty($queryParameters)) {
            $queryString = http_build_query($queryParameters);
        }

        return $path . (!empty($queryString) ? '?' . $queryString : '');
    }
}
