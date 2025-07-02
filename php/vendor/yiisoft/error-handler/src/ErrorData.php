<?php

declare(strict_types=1);

namespace Yiisoft\ErrorHandler;

use Psr\Http\Message\ResponseInterface;
use Stringable;

/**
 * ErrorData stores content and headers that are suitable for adding to response.
 */
final class ErrorData implements Stringable
{
    /**
     * @param string $content The content to use as response body.
     * @param array<string, string|string[]> $headers The headers to add to the response.
     */
    public function __construct(
        private string $content,
        private array $headers = [],
    ) {
    }

    /**
     * Returns a content to use as response body.
     *
     * @return string The content to use as response body.
     */
    public function __toString(): string
    {
        return $this->content;
    }

    /**
     * Returns a response with error data.
     *
     * @param ResponseInterface $response The response for setting error data.
     *
     * @return ResponseInterface The response with error data.
     */
    public function addToResponse(ResponseInterface $response): ResponseInterface
    {
        foreach ($this->headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $response
            ->getBody()
            ->write($this->content);
        return $response;
    }
}
