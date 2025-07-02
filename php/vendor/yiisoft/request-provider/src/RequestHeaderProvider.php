<?php

declare(strict_types=1);

namespace Yiisoft\RequestProvider;

/**
 * The `RequestHeaderProvider` class provides utility methods for retrieving HTTP headers
 * from a request. It uses a `RequestProviderInterface` implementation to access the current request.
 */
final class RequestHeaderProvider
{
    /**
     * Initializes the instance with a request provider.
     *
     * @param RequestProviderInterface $requestProvider The request provider to access the request.
     */
    public function __construct(
        private readonly RequestProviderInterface $requestProvider
    ) {
    }

    /**
     * Retrieves the value of a specific header as a string. If the header does not exist, returns default value.
     *
     * @param string $name The name of the header to retrieve.
     * @return string|null The header value as a string, or default value if the header is not present.
     */
    public function getLine(string $name, string|null $default = null): string|null
    {
        $request = $this->requestProvider->get();
        return $request->hasHeader($name) ? $request->getHeaderLine($name) : $default;
    }

    /**
     * Retrieves the value(s) of a specific header as an array.
     *
     * @param string $name The name of the header to retrieve.
     * @return string[] An array of header values, or an empty array if the header is not present.
     */
    public function get(string $name): array
    {
        return $this->requestProvider->get()->getHeader($name);
    }

    /**
     * Retrieves all headers as an associative array where the key is the header name
     * and the value is an array of its values.
     *
     * @return string[][] An associative array of all headers.
     */
    public function getAll(): array
    {
        return $this->requestProvider->get()->getHeaders();
    }

    /**
     * Retrieves the first value of each header as an associative array where the key is the header name
     * and the value is the first header value.
     *
     * @return string[] An associative array of the first values of all headers.
     */
    public function getFirstHeaders(): array
    {
        return array_map(static fn(array $lines) => $lines[0], $this->getAll());
    }

    /**
     * Checks if a specific header is present in the request.
     *
     * @param string $name The name of the header to check.
     * @return bool True if the header is present, false otherwise.
     */
    public function has(string $name): bool
    {
        return $this->requestProvider->get()->hasHeader($name);
    }
}
