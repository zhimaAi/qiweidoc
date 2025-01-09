<?php

namespace Common;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class HttpClient
{
    /**
     * @var Client
     */
    protected Client $client;

    /**
     * @var LoggerInterface|null
     */
    protected ?LoggerInterface $logger;

    /**
     * @var array
     */
    protected array $defaultConfig = [
        'base_uri' => '',
        'timeout' => 30,
        'connect_timeout' => 5,
        'verify' => false,
        'http_errors' => true,
        'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ],
    ];

    /**
     * @var array
     */
    protected array $currentConfig;

    /**
     * @param array $config
     * @param LoggerInterface|null $logger
     */
    public function __construct(array $config = [], LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->currentConfig = array_merge($this->defaultConfig, $config);
        $this->initClient();
    }

    /**
     * @param array $config
     * @return void
     */
    protected function initClient(): void
    {
        $stack = HandlerStack::create();

        // Add retry middleware
        $stack->push($this->retryMiddleware());

        // Add logging middleware if logger is provided
        if ($this->logger) {
            $stack->push($this->loggingMiddleware());
        }

        // Merge default config with provided config
        $mergedConfig = array_merge($this->currentConfig, [
            'handler' => $stack,
        ]);

        $this->client = new Client($mergedConfig);
    }

    /**
     * @return callable
     */
    protected function retryMiddleware(): callable
    {
        return Middleware::retry(
            function (
                $retries,
                $request,
                $response = null,
                $exception = null
            ) {
                if ($retries >= 3) {
                    return false;
                }

                if ($exception instanceof ConnectException) {
                    return true;
                }

                if ($response && $response->getStatusCode() >= 500) {
                    return true;
                }

                return false;
            },
            function ($retries) {
                return 1000 * pow(2, $retries);
            }
        );
    }

    /**
     * @return callable
     */
    protected function loggingMiddleware(): callable
    {
        return Middleware::log(
            $this->logger,
            new MessageFormatter("[{date_iso_8601}] {method} {uri} {code} {res_header_Content-Length}")
        );
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return ResponseInterface
     * @throws Exception
     */
    protected function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        try {
            return $this->client->request($method, $uri, $options);
        } catch (RequestException $e) {
            $this->handleRequestException($e);
        } catch (GuzzleException $e) {
            throw new LogicException('HTTP Client Error: ' . $e->getMessage(), $e->getCode());
        }
    }

    /**
     * 异步请求
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return PromiseInterface
     */
    protected function requestAsync(string $method, string $uri, array $options = []): PromiseInterface
    {
        return $this->client->requestAsync($method, $uri, $options)
            ->then(
                function (ResponseInterface $response) {
                    return $response;
                },
                function ($exception) {
                    if ($exception instanceof RequestException) {
                        $this->handleRequestException($exception);
                    }
                    throw new Exception('HTTP Client Error: ' . $exception->getMessage(), $exception->getCode());
                }
            );
    }

    /**
     * @param RequestException $e
     * @throws Exception
     */
    protected function handleRequestException(RequestException $e): void
    {
        $response = $e->getResponse();
        if (!$response) {
            throw new LogicException('Network Error: ' . $e->getMessage(), 0);
        }

        $statusCode = $response->getStatusCode();
        $body = json_decode($response->getBody()->getContents(), true);

        $message = $body['message'] ?? $e->getMessage();

        throw new LogicException("HTTP Error {$statusCode}: {$message}", $statusCode);
    }

    /**
     * @param string $uri
     * @param array $query
     * @param array $options
     * @return ResponseInterface
     * @throws Exception
     */
    public function get(string $uri, array $query = [], array $options = []): ResponseInterface
    {
        $options['query'] = $query;

        return $this->request('GET', $uri, $options);
    }

    /**
     * 异步 GET 请求
     *
     * @param string $uri
     * @param array $query
     * @param array $options
     * @return PromiseInterface
     */
    public function getAsync(string $uri, array $query = [], array $options = []): PromiseInterface
    {
        $options['query'] = $query;

        return $this->requestAsync('GET', $uri, $options);
    }

    /**
     * @param string $uri
     * @param array $data
     * @param array $options
     * @return ResponseInterface
     * @throws Exception
     */
    public function post(string $uri, array $data = [], array $options = []): ResponseInterface
    {
        $options['json'] = $data;

        return $this->request('POST', $uri, $options);
    }

    /**
     * 异步 POST 请求
     *
     * @param string $uri
     * @param array $data
     * @param array $options
     * @return PromiseInterface
     */
    public function postAsync(string $uri, array $data = [], array $options = []): PromiseInterface
    {
        $options['json'] = $data;

        return $this->requestAsync('POST', $uri, $options);
    }

    /**
     * @param string $uri
     * @param array $data
     * @param array $options
     * @return ResponseInterface
     * @throws Exception
     */
    public function put(string $uri, array $data = [], array $options = []): ResponseInterface
    {
        $options['json'] = $data;

        return $this->request('PUT', $uri, $options);
    }

    /**
     * 异步 PUT 请求
     *
     * @param string $uri
     * @param array $data
     * @param array $options
     * @return PromiseInterface
     */
    public function putAsync(string $uri, array $data = [], array $options = []): PromiseInterface
    {
        $options['json'] = $data;

        return $this->requestAsync('PUT', $uri, $options);
    }

    /**
     * @param string $uri
     * @param array $options
     * @return ResponseInterface
     * @throws Exception
     */
    public function delete(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('DELETE', $uri, $options);
    }

    /**
     * 异步 DELETE 请求
     *
     * @param string $uri
     * @param array $options
     * @return PromiseInterface
     */
    public function deleteAsync(string $uri, array $options = []): PromiseInterface
    {
        return $this->requestAsync('DELETE', $uri, $options);
    }

    /**
     * @param string $token
     * @return self
     */
    public function withToken(string $token): self
    {
        // Update the current configuration with the new Authorization header
        $this->currentConfig['headers']['Authorization'] = 'Bearer ' . $token;

        // Re-initialize the client with the updated configuration
        $this->initClient();

        return $this;
    }
}
