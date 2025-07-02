<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
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
use function is_resource;
use function is_string;
use function preg_match;
use function str_replace;
use function strtolower;
use function substr;
use function ucwords;

/**
 * `ServerRequestFactory` creates an instance of a server request.
 *
 * @deprecated Will remove in the next major version.
 */
final class ServerRequestFactory
{
    public function __construct(
        private ServerRequestFactoryInterface $serverRequestFactory,
        private UriFactoryInterface $uriFactory,
        private UploadedFileFactoryInterface $uploadedFileFactory,
        private StreamFactoryInterface $streamFactory
    ) {
    }

    /**
     * Creates an instance of a server request from PHP superglobals.
     *
     * @return ServerRequestInterface The server request instance.
     */
    public function createFromGlobals(): ServerRequestInterface
    {
        /** @psalm-var array<string, string> $_SERVER */
        return $this->createFromParameters(
            $_SERVER,
            $this->getHeadersFromGlobals(),
            $_COOKIE,
            $_GET,
            $_POST,
            $_FILES,
            fopen('php://input', 'rb') ?: null
        );
    }

    /**
     * Creates an instance of a server request from custom parameters.
     *
     * @param resource|StreamInterface|string|null $body
     *
     * @psalm-param array<string, string> $server
     * @psalm-param array<string, string|string[]> $headers
     * @psalm-param mixed $body
     *
     * @return ServerRequestInterface The server request instance.
     */
    public function createFromParameters(
        array $server,
        array $headers = [],
        array $cookies = [],
        array $get = [],
        array $post = [],
        array $files = [],
        mixed $body = null
    ): ServerRequestInterface {
        $method = $server['REQUEST_METHOD'] ?? null;

        if ($method === null) {
            throw new RuntimeException('Unable to determine HTTP request method.');
        }

        $uri = $this->getUri($server);
        $request = $this->serverRequestFactory->createServerRequest($method, $uri, $server);

        foreach ($headers as $name => $value) {
            if ($name === 'Host' && $request->hasHeader('Host')) {
                continue;
            }

            $request = $request->withAddedHeader($name, $value);
        }

        $protocol = '1.1';
        if (array_key_exists('SERVER_PROTOCOL', $server) && $server['SERVER_PROTOCOL'] !== '') {
            $protocol = str_replace('HTTP/', '', $server['SERVER_PROTOCOL']);
        }

        $request = $request
            ->withProtocolVersion($protocol)
            ->withQueryParams($get)
            ->withParsedBody($post)
            ->withCookieParams($cookies)
            ->withUploadedFiles($this->getUploadedFilesArray($files))
        ;

        if ($body === null) {
            return $request;
        }

        if ($body instanceof StreamInterface) {
            return $request->withBody($body);
        }

        if (is_string($body)) {
            return $request->withBody($this->streamFactory->createStream($body));
        }

        if (is_resource($body)) {
            return $request->withBody($this->streamFactory->createStreamFromResource($body));
        }

        throw new InvalidArgumentException(
            'Body parameter for "ServerRequestFactory::createFromParameters()"'
            . 'must be instance of StreamInterface, resource or null.',
        );
    }

    /**
     * @psalm-param array<string, string> $server
     */
    private function getUri(array $server): UriInterface
    {
        $uri = $this->uriFactory->createUri();

        if (array_key_exists('HTTPS', $server) && $server['HTTPS'] !== '' && $server['HTTPS'] !== 'off') {
            $uri = $uri->withScheme('https');
        } else {
            $uri = $uri->withScheme('http');
        }

        $uri = isset($server['SERVER_PORT'])
            ? $uri->withPort((int)$server['SERVER_PORT'])
            : $uri->withPort($uri->getScheme() === 'https' ? 443 : 80);

        if (isset($server['HTTP_HOST'])) {
            $uri = preg_match('/^(.+):(\d+)$/', $server['HTTP_HOST'], $matches) === 1
                ? $uri
                    ->withHost($matches[1])
                    ->withPort((int) $matches[2])
                : $uri->withHost($server['HTTP_HOST'])
            ;
        } elseif (isset($server['SERVER_NAME'])) {
            $uri = $uri->withHost($server['SERVER_NAME']);
        }

        if (isset($server['REQUEST_URI'])) {
            $uri = $uri->withPath(explode('?', $server['REQUEST_URI'])[0]);
        }

        if (isset($server['QUERY_STRING'])) {
            $uri = $uri->withQuery($server['QUERY_STRING']);
        }

        return $uri;
    }

    /**
     * @psalm-return array<string, string>
     */
    private function getHeadersFromGlobals(): array
    {
        if (function_exists('getallheaders') && ($headers = getallheaders()) !== false) {
            /** @psalm-var array<string, string> $headers */
            return $headers;
        }

        $headers = [];

        /**
         * @var string $name
         * @var string $value
         */
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

    private function getUploadedFilesArray(array $filesArray): array
    {
        $files = [];

        /** @var array $info */
        foreach ($filesArray as $class => $info) {
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

        return $files;
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
