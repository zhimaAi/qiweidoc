<?php

declare(strict_types=1);

namespace Yiisoft\ErrorHandler;

use Yiisoft\ErrorHandler\Middleware\ErrorCatcher;

/**
 * `HeadersProvider` provides headers for error response.
 * It is used by {@see ErrorCatcher} to add headers to response in case of error.
 */
final class HeadersProvider
{
    /**
     * @param array<string, string[]> $headers Default headers list.
     */
    public function __construct(
        private array $headers = [],
    ) {
    }

    /**
     * Adds a header to the list of headers.
     *
     * @param string $name The header name.
     * @param string|string[] $values The header value.
     */
    public function add(string $name, string|array $values): void
    {
        $this->headers[$name] = (array)$values;
    }

    /**
     * Returns all headers.
     *
     * @return array<string, string[]> The headers list.
     */
    public function getAll(): array
    {
        return $this->headers;
    }
}
