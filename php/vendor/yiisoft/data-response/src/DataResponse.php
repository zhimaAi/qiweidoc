<?php

declare(strict_types=1);

namespace Yiisoft\DataResponse;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

use function ftruncate;
use function is_callable;
use function is_object;
use function is_resource;
use function is_string;
use function rewind;
use function sprintf;

/**
 * A wrapper around PSR-7 response that is assigned raw data to be formatted with a formatter later.
 *
 * For example, `['name' => 'Dmitriy']` to be formatted as JSON using
 * {@see \Yiisoft\DataResponse\Formatter\JsonDataResponseFormatter} when {@see DataResponse::getBody()} is called.
 */
final class DataResponse implements ResponseInterface
{
    /**
     * @var resource
     */
    private $resource;

    private bool $formatted = false;
    private bool $forcedBody = false;
    private ResponseInterface $response;
    private ?StreamInterface $dataStream = null;
    private ?DataResponseFormatterInterface $responseFormatter = null;

    /**
     * @param mixed $data The response data.
     * @param int $code The response status code.
     * @param string $reasonPhrase The response reason phrase associated with the status code.
     * @param ResponseFactoryInterface $responseFactory The response factory instance.
     * @param StreamFactoryInterface $streamFactory The stream factory instance.
     */
    public function __construct(
        private mixed $data,
        int $code,
        string $reasonPhrase,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->createResponse($code, $reasonPhrase, $responseFactory, $streamFactory);
    }

    public function getBody(): StreamInterface
    {
        if ($this->dataStream !== null) {
            return $this->dataStream;
        }

        if ($this->hasResponseFormatter()) {
            $this->formatResponse();
            return $this->dataStream = $this->response->getBody();
        }

        if ($this->data === null) {
            $this->clearResponseBody();
            return $this->dataStream = $this->response->getBody();
        }

        $data = $this->getData();

        if (is_string($data)) {
            $this->clearResponseBody();
            $this->response
                ->getBody()
                ->write($data);
            return $this->dataStream = $this->response->getBody();
        }

        throw new RuntimeException(sprintf(
            'The data is "%s" not a string. To get non-string data, use the "%s::getData()" method.',
            get_debug_type($data),
            self::class,
        ));
    }

    /**
     * @inheritDoc
     *
     * @param string $name
     *
     * @return string[]
     */
    public function getHeader($name): array
    {
        $this->formatResponse();
        return $this->response->getHeader($name);
    }

    /**
     * @inheritDoc
     *
     * @param string $name
     */
    public function getHeaderLine($name): string
    {
        $this->formatResponse();
        return $this->response->getHeaderLine($name);
    }

    /**
     * @inheritDoc
     *
     * @return string[][]
     */
    public function getHeaders(): array
    {
        $this->formatResponse();
        return $this->response->getHeaders();
    }

    public function getProtocolVersion(): string
    {
        $this->formatResponse();
        return $this->response->getProtocolVersion();
    }

    public function getReasonPhrase(): string
    {
        $this->formatResponse();
        return $this->response->getReasonPhrase();
    }

    public function getStatusCode(): int
    {
        $this->formatResponse();
        return $this->response->getStatusCode();
    }

    /**
     * @inheritDoc
     *
     * @param string $name
     */
    public function hasHeader($name): bool
    {
        $this->formatResponse();
        return $this->response->hasHeader($name);
    }

    /**
     * @inheritDoc
     *
     * @param string $name
     * @param string|string[] $value
     *
     * @return self
     */
    public function withAddedHeader($name, $value): self
    {
        $new = clone $this;
        $new->response = $this->response->withAddedHeader($name, $value);
        $new->formatted = false;
        return $new;
    }

    /**
     * @inheritDoc
     *
     * @return self
     */
    public function withBody(StreamInterface $body): self
    {
        $new = clone $this;
        $new->response = $this->response->withBody($body);
        $new->dataStream = $body;
        $new->forcedBody = true;
        $new->formatted = false;
        $new->data = null;
        return $new;
    }

    /**
     * @inheritDoc
     *
     * @param string $name
     * @param string|string[] $value
     *
     * @return self
     */
    public function withHeader($name, $value): self
    {
        $new = clone $this;
        $new->response = $this->response->withHeader($name, $value);
        $new->formatted = false;
        return $new;
    }

    /**
     * @inheritDoc
     *
     * @param string $name
     *
     * @return self
     */
    public function withoutHeader($name): self
    {
        $new = clone $this;
        $new->formatResponse();
        $new->response = $new->response->withoutHeader($name);
        return $new;
    }

    /**
     * @inheritDoc
     *
     * @param string $version
     *
     * @return self
     */
    public function withProtocolVersion($version): self
    {
        $new = clone $this;
        $new->response = $this->response->withProtocolVersion($version);
        $new->formatted = false;
        return $new;
    }

    /**
     * @inheritDoc
     *
     * @param int $code
     * @param string $reasonPhrase
     *
     * @return self
     */
    public function withStatus($code, $reasonPhrase = ''): self
    {
        $new = clone $this;
        $new->response = $this->response->withStatus($code, $reasonPhrase);
        $new->formatted = false;
        return $new;
    }

    /**
     * Returns a new instance with the specified response formatter.
     */
    public function withResponseFormatter(DataResponseFormatterInterface $responseFormatter): self
    {
        $new = clone $this;
        $new->responseFormatter = $responseFormatter;
        $new->formatted = false;
        return $new;
    }

    /**
     * Returns the original instance of the response.
     *
     * @return ResponseInterface The original instance of the response.
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Checks whether the response formatter has been set {@see withResponseFormatter()}.
     *
     * @return bool Whether the formatter has been set.
     */
    public function hasResponseFormatter(): bool
    {
        return $this->responseFormatter !== null;
    }

    /**
     * Returns a new instance with the specified response data.
     *
     * @param mixed $data The response data.
     *
     * @throws RuntimeException If the body was previously forced to be set {@see withBody()}.
     *
     * @return self
     */
    public function withData(mixed $data): self
    {
        if ($this->forcedBody) {
            throw new RuntimeException(sprintf(
                'The data cannot be set because the body was previously'
                . ' forced to be set using the "%s::withBody()" method.',
                self::class,
            ));
        }

        $new = clone $this;
        $new->data = $data;
        $new->dataStream = null;
        $new->formatted = false;
        return $new;
    }

    /**
     * Returns the response data.
     *
     * If the response data is a PHP callable, the result of the PHP callable execute will be returned.
     * If the response data or the result of the execution of the PHP callable is an object,
     * a cloned copy of this object will be returned.
     *
     * @return mixed
     */
    public function getData()
    {
        if (is_callable($this->data)) {
            $this->data = ($this->data)();
        }

        return is_object($this->data) ? clone $this->data : $this->data;
    }

    /**
     * Checks whether the response data has been set.
     *
     * @return bool Whether the response data has been set.
     */
    public function hasData(): bool
    {
        return $this->getData() !== null;
    }

    /**
     * Formats the response, if necessary.
     */
    private function formatResponse(): void
    {
        if ($this->formatted || !$this->hasResponseFormatter()) {
            return;
        }

        /** @psalm-var DataResponseFormatterInterface $this->responseFormatter */

        $this->clearResponseBody();
        $this->formatted = true;
        $response = $this->responseFormatter->format($this);

        if ($response instanceof self) {
            throw new RuntimeException(sprintf(
                'The "%s::format()" method should not return instance of "%s".',
                DataResponseFormatterInterface::class,
                self::class,
            ));
        }

        $this->response = $response;
    }

    /**
     * Clears a response body.
     */
    private function clearResponseBody(): void
    {
        if (!$this->forcedBody) {
            ftruncate($this->resource, 0);
            rewind($this->resource);
        }
    }

    /**
     * Creates a new response by retrieving and validating the stream resource.
     *
     * @param int $code The response status code.
     * @param string $reasonPhrase The response reason phrase associated with the status code.
     * @param ResponseFactoryInterface $responseFactory The response factory instance.
     * @param StreamFactoryInterface $streamFactory The stream factory instance.
     *
     * @throws RuntimeException If the stream resource is not valid.
     */
    private function createResponse(
        int $code,
        string $reasonPhrase,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory
    ): void {
        $response = $responseFactory->createResponse($code, $reasonPhrase);
        $stream = $response->getBody();

        if (!$stream->isReadable()) {
            throw new RuntimeException('Stream is not readable.');
        }

        if (!$stream->isSeekable()) {
            throw new RuntimeException('Stream is not seekable.');
        }

        if (!$stream->isWritable()) {
            throw new RuntimeException('Stream is not writable.');
        }

        if (!is_resource($resource = $stream->detach())) {
            throw new RuntimeException('Resource was not separated from the stream.');
        }

        $this->resource = $resource;
        $this->response = $response->withBody($streamFactory->createStreamFromResource($this->resource));
    }
}
