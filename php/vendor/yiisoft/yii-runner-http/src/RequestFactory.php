<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

use function array_key_exists;
use function explode;
use function fopen;
use function function_exists;
use function getallheaders;
use function is_array;
use function preg_match;
use function str_replace;
use function strtolower;
use function substr;
use function ucwords;

/**
 * `RequestFactory` creates an instance of a server request.
 *
 * @internal
 */
final class RequestFactory
{
    public function __construct(
        private ServerRequestFactoryInterface $serverRequestFactory,
        private UriFactoryInterface $uriFactory,
        private UploadedFileFactoryInterface $uploadedFileFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    /**
     * Creates an instance of a server request from custom parameters.
     *
     * @param false|resource|null $body
     *
     * @return ServerRequestInterface The server request instance.
     */
    public function create($body = null): ServerRequestInterface
    {
        // Create base request
        $method = $_SERVER['REQUEST_METHOD'] ?? null;
        if ($method === null) {
            throw new RuntimeException('Unable to determine HTTP request method.');
        }
        $request = $this->serverRequestFactory->createServerRequest($method, $this->createUri(), $_SERVER);

        // Add headers
        foreach ($this->getHeaders() as $name => $value) {
            if ($name === 'Host' && $request->hasHeader('Host')) {
                continue;
            }
            $request = $request->withAddedHeader($name, $value);
        }

        // Add protocol
        $protocol = '1.1';
        /** @psalm-suppress RedundantCondition It's bug in Psalm < 5 */
        if (array_key_exists('SERVER_PROTOCOL', $_SERVER) && $_SERVER['SERVER_PROTOCOL'] !== '') {
            $protocol = str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']);
        }
        $request = $request->withProtocolVersion($protocol);

        // Add body
        $body ??= fopen('php://input', 'rb');
        if ($body !== false) {
            $request = $request->withBody(
                $this->streamFactory->createStreamFromResource($body)
            );
        }

        // Parse body
        if ($method === 'POST') {
            $contentType = $request->getHeaderLine('content-type');
            if (preg_match('~^application/x-www-form-urlencoded($| |;)~', $contentType)
                || preg_match('~^multipart/form-data($| |;)~', $contentType)
            ) {
                $request = $request->withParsedBody($_POST);
            }
        }

        // Add query and cookie params
        $request = $request
            ->withQueryParams($_GET)
            ->withCookieParams($_COOKIE);

        // Add uploaded files
        $files = [];
        /** @psalm-suppress PossiblyInvalidArrayAccess,PossiblyInvalidArrayOffset It's bug in Psalm < 5 */
        foreach ($_FILES as $class => $info) {
            $files[$class] = [];
            $this->populateUploadedFileRecursive(
                $files[$class],
                $info['name'],
                $info['tmp_name'],
                $info['type'],
                $info['size'],
                $info['error'],
            );
        }
        $request = $request->withUploadedFiles($files);

        return $request;
    }

    private function createUri(): UriInterface
    {
        $uri = $this->uriFactory->createUri();

        if (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off') {
            $uri = $uri->withScheme('https');
        } else {
            $uri = $uri->withScheme('http');
        }

        $uri = isset($_SERVER['SERVER_PORT'])
            ? $uri->withPort((int) $_SERVER['SERVER_PORT'])
            : $uri->withPort($uri->getScheme() === 'https' ? 443 : 80);

        if (isset($_SERVER['HTTP_HOST'])) {
            $uri = preg_match('/^(.+):(\d+)$/', $_SERVER['HTTP_HOST'], $matches) === 1
                ? $uri
                    ->withHost($matches[1])
                    ->withPort((int) $matches[2])
                : $uri->withHost($_SERVER['HTTP_HOST']);
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            $uri = $uri->withHost($_SERVER['SERVER_NAME']);
        }

        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = $uri->withPath(explode('?', $_SERVER['REQUEST_URI'])[0]);
        }

        if (isset($_SERVER['QUERY_STRING'])) {
            $uri = $uri->withQuery($_SERVER['QUERY_STRING']);
        }

        return $uri;
    }

    /**
     * @psalm-return array<string, string>
     */
    private function getHeaders(): array
    {
        /** @psalm-var array<string, string> $_SERVER */

        if (function_exists('getallheaders') && ($headers = getallheaders()) !== false) {
            /** @psalm-var array<string, string> $headers */
            return $headers;
        }

        $headers = [];

        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'REDIRECT_')) {
                $name = substr($name, 9);

                if (array_key_exists($name, $_SERVER)) {
                    continue;
                }
            }

            if (str_starts_with($name, 'HTTP_')) {
                $headers[$this->normalizeHeaderName(substr($name, 5))] = $value;
                continue;
            }

            if (str_starts_with($name, 'CONTENT_')) {
                $headers[$this->normalizeHeaderName($name)] = $value;
            }
        }

        return $headers;
    }

    private function normalizeHeaderName(string $name): string
    {
        return str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name))));
    }

    /**
     * Populates uploaded files array from $_FILE data structure recursively.
     *
     * @param array $files Uploaded files array to be populated.
     * @param mixed $names File names provided by PHP.
     * @param mixed $tempNames Temporary file names provided by PHP.
     * @param mixed $types File types provided by PHP.
     * @param mixed $sizes File sizes provided by PHP.
     * @param mixed $errors Uploading issues provided by PHP.
     *
     * @psalm-suppress MixedArgument, ReferenceConstraintViolation
     */
    private function populateUploadedFileRecursive(
        array &$files,
        mixed $names,
        mixed $tempNames,
        mixed $types,
        mixed $sizes,
        mixed $errors
    ): void {
        if (is_array($names)) {
            /** @var array|string $name */
            foreach ($names as $i => $name) {
                $files[$i] = [];
                /** @psalm-suppress MixedArrayAccess */
                $this->populateUploadedFileRecursive(
                    $files[$i],
                    $name,
                    $tempNames[$i],
                    $types[$i],
                    $sizes[$i],
                    $errors[$i],
                );
            }

            return;
        }

        try {
            $stream = $this->streamFactory->createStreamFromFile($tempNames);
        } catch (RuntimeException) {
            $stream = $this->streamFactory->createStream();
        }

        $files = $this->uploadedFileFactory->createUploadedFile(
            $stream,
            (int) $sizes,
            (int) $errors,
            $names,
            $types
        );
    }
}
