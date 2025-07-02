<?php

declare(strict_types=1);

namespace Yiisoft\DataResponse;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Header;

/**
 * ResponseContentTrait provides methods for manipulating the response content.
 */
trait ResponseContentTrait
{
    /**
     * Returns a new instance with the specified content type.
     *
     * @param string $contentType The content type. For example, "text/html".
     *
     * @return self
     */
    public function withContentType(string $contentType): self
    {
        $new = clone $this;
        $new->contentType = $contentType;
        return $new;
    }

    /**
     * Returns a new instance with the specified encoding.
     *
     * @param string $encoding The encoding. For example, "UTF-8".
     *
     * @return self
     */
    public function withEncoding(string $encoding): self
    {
        $new = clone $this;
        $new->encoding = $encoding;
        return $new;
    }

    /**
     * Adds the content and the content type header to the response and returns it.
     *
     * @param ResponseInterface $response The response instance.
     * @param string|null $content The content to add to the response.
     *
     * @return ResponseInterface A response with added content and a content type header.
     */
    private function addToResponse(ResponseInterface $response, ?string $content = null): ResponseInterface
    {
        if ($content !== null) {
            $response
                ->getBody()
                ->write($content);
        }

        return $response->withHeader(Header::CONTENT_TYPE, "$this->contentType; charset=$this->encoding");
    }
}
