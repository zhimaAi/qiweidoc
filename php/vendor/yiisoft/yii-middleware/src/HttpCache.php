<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Header;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;

use function base64_encode;
use function in_array;
use function rtrim;
use function preg_split;
use function sha1;
use function str_replace;
use function strtotime;

/**
 * HttpCache implements client-side caching by utilizing the `Last-Modified` and `ETag` HTTP headers.
 */
final class HttpCache implements MiddlewareInterface
{
    /**
     * @var callable|null
     */
    private $lastModified = null;

    /**
     * @var callable|null
     */
    private $etagSeed = null;

    private bool $weakEtag = false;
    private mixed $params = null;
    private ?string $cacheControlHeader = 'public, max-age=3600';

    /**
     * Returns a new instance with the specified callable that generates the last modified UNIX timestamp.
     *
     * @param callable $lastModified A PHP callback that returns the UNIX timestamp of the last modification time.
     *
     * The callback's signature should be:
     *
     * ```php
     * function (ServerRequestInterface $request, mixed $params): int;
     * ```
     *
     * Where `$request` is the {@see ServerRequestInterface} object that this filter is currently handling;
     * `$params` takes the value of {@see withParams()}. The callback should return a UNIX timestamp.
     *
     * @see https://tools.ietf.org/html/rfc7232#section-2.2
     */
    public function withLastModified(callable $lastModified): self
    {
        $new = clone $this;
        $new->lastModified = $lastModified;
        return $new;
    }

    /**
     * Returns a new instance with the specified callable that generates the ETag seed string.
     *
     * @param callable $etagSeed A PHP callback that generates the ETag seed string.
     *
     * The callback's signature should be:
     *
     * ```php
     * function (ServerRequestInterface $request, mixed $params): string;
     * ```
     *
     * Where `$request` is the {@see ServerRequestInterface} object that this middleware is currently handling;
     * `$params` takes the value of {@see withParams()}. The callback should return a string serving
     * as the seed for generating an ETag.
     */
    public function withEtagSeed(callable $etagSeed): self
    {
        $new = clone $this;
        $new->etagSeed = $etagSeed;
        return $new;
    }

    /**
     * Returns a new instance with weak ETags generation enabled. Disabled by default.
     *
     * You should use weak ETags if the content is semantically equal, but not byte-equal.
     *
     * @see https://tools.ietf.org/html/rfc7232#section-2.3
     */
    public function withWeakEtag(): self
    {
        $new = clone $this;
        $new->weakEtag = true;
        return $new;
    }

    /**
     * Returns a new instance with the specified extra parameters for ETag seed string generation.
     *
     * @param mixed $params Extra parameters for {@see withEtagSeed()} callbacks.
     */
    public function withParams(mixed $params): self
    {
        $new = clone $this;
        $new->params = $params;
        return $new;
    }

    /**
     * Returns a new instance with the specified value of the `Cache-Control` HTTP header.
     *
     * @param string|null $header The value of the `Cache-Control` HTTP header. If `null`, the header won't be sent.
     *
     * @see https://tools.ietf.org/html/rfc2616#section-14.9
     */
    public function withCacheControlHeader(?string $header): self
    {
        $new = clone $this;
        $new->cacheControlHeader = $header;
        return $new;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (
            ($this->lastModified === null && $this->etagSeed === null) ||
            !in_array($request->getMethod(), [Method::GET, Method::HEAD], true)
        ) {
            return $handler->handle($request);
        }

        /** @var int|null $lastModified */
        $lastModified = $this->lastModified === null ? null : ($this->lastModified)($request, $this->params);
        $etag = null;

        if ($this->etagSeed !== null) {
            /** @var string|null $seed */
            $seed = ($this->etagSeed)($request, $this->params);

            if ($seed !== null) {
                $etag = $this->generateEtag($seed);
            }
        }

        $response = $handler->handle($request);

        if ($this->cacheControlHeader !== null) {
            $response = $response->withHeader(Header::CACHE_CONTROL, $this->cacheControlHeader);
        }
        if ($etag !== null) {
            $response = $response->withHeader(Header::ETAG, $etag);
        }

        $cacheIsValid = $this->validateCache($request, $lastModified, $etag);
        if ($cacheIsValid) {
            $response = $response->withStatus(Status::NOT_MODIFIED);
        }

        /** @see https://tools.ietf.org/html/rfc7232#section-4.1 */
        if ($lastModified !== null && (!$cacheIsValid || $etag === null)) {
            $response = $response->withHeader(
                Header::LAST_MODIFIED,
                gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
            );
        }

        return $response;
    }

    /**
     * Validates if the HTTP cache has valid content.
     * If both `Last-Modified` and `ETag` are `null`, it returns `false`.
     *
     * @param ServerRequestInterface $request The server request instance.
     * @param int|null $lastModified The calculated Last-Modified value in terms of a UNIX timestamp.
     * If `null`, the `Last-Modified` header won't be validated.
     * @param string|null $etag The calculated `ETag` value. If `null`, the `ETag` header won't be validated.
     *
     * @return bool Whether the HTTP cache is still valid.
     */
    private function validateCache(ServerRequestInterface $request, ?int $lastModified, ?string $etag): bool
    {
        if ($request->hasHeader(Header::IF_NONE_MATCH)) {
            if ($etag === null) {
                return false;
            }

            $headerParts = preg_split(
                '/[\s,]+/',
                str_replace('-gzip', '', $request->getHeaderLine(Header::IF_NONE_MATCH)),
                flags: PREG_SPLIT_NO_EMPTY,
            );

            // "HTTP_IF_NONE_MATCH" takes precedence over "HTTP_IF_MODIFIED_SINCE".
            // https://tools.ietf.org/html/rfc7232#section-3.3
            return $headerParts !== false && in_array($etag, $headerParts, true);
        }

        if ($request->hasHeader(Header::IF_MODIFIED_SINCE)) {
            $header = $request->getHeaderLine(Header::IF_MODIFIED_SINCE);
            return $lastModified !== null && @strtotime($header) >= $lastModified;
        }

        return false;
    }

    /**
     * Generates an ETag from the given seed string.
     *
     * @param string $seed Seed for the ETag.
     *
     * @return string The generated ETag.
     */
    private function generateEtag(string $seed): string
    {
        $etag = '"' . rtrim(base64_encode(sha1($seed, true)), '=') . '"';
        return $this->weakEtag ? 'W/' . $etag : $etag;
    }
}
