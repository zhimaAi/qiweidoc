<?php

declare(strict_types=1);

namespace Yiisoft\VarDumper\Handler;

use InvalidArgumentException;
use RuntimeException;
use Socket;
use Yiisoft\VarDumper\HandlerInterface;

use function fsockopen;
use function fwrite;
use function get_debug_type;
use function is_resource;
use function is_string;

/**
 * Uses stream ({@link https://www.php.net/manual/en/intro.stream.php}) for writing variable's data. Requires "sockets"
 * PHP extension when {@see StreamHandler::$uri} is a {@see Socket} instance.
 */
final class StreamHandler implements HandlerInterface
{
    /**
     * @var callable|null
     */
    private mixed $encoder = null;
    /**
     * @var resource|Socket|null
     */
    private mixed $stream = null;

    /**
     * @var resource|Socket|string
     */
    private mixed $uri;

    private const SOCKET_PROTOCOLS = ['udp', 'udg', 'tcp', 'unix'];

    /**
     * @param mixed|resource|string $uri
     */
    public function __construct(
        mixed $uri = 'udp://127.0.0.1:8890'
    ) {
        if (!is_string($uri) && !is_resource($uri) && !$uri instanceof Socket) {
            throw new InvalidArgumentException(
                sprintf(
                    'Argument $uri must be either a string, a resource or a Socket instance, "%s" given.',
                    get_debug_type($uri)
                )
            );
        }
        $this->uri = $uri;
    }

    public function __destruct()
    {
        if (!is_string($this->uri) || !is_resource($this->stream)) {
            return;
        }
        fclose($this->stream);
    }

    /**
     * Encodes {@param $variable} with {@see self::$encoder} and sends the result to the stream.
     */
    public function handle(mixed $variable, int $depth, bool $highlight = false): void
    {
        $data = ($this->encoder ?? '\json_encode')($variable);
        if (!is_string($data)) {
            throw new RuntimeException(
                sprintf(
                    'Encoder must return a string, "%s" returned.',
                    get_debug_type($data)
                )
            );
        }

        if (!is_resource($this->stream) && !$this->stream instanceof Socket) {
            $this->initializeStream();
        }

        if (!$this->writeToStream($data)) {
            $this->initializeStream();

            if (!$this->writeToStream($data)) {
                throw new RuntimeException('Cannot write a stream.');
            }
        }
    }

    /**
     * @param callable(mixed $variable): string $encoder Encoder that will be used to encode variable before sending it to the stream.
     */
    public function withEncoder(callable $encoder): static
    {
        $new = clone $this;
        $new->encoder = $encoder;
        return $new;
    }

    private function initializeStream(): void
    {
        if (!is_string($this->uri)) {
            $this->stream = $this->uri;
        } else {
            $uriHasSocketProtocol = false;
            foreach (self::SOCKET_PROTOCOLS as $protocol) {
                if (str_starts_with($this->uri, "$protocol://")) {
                    $uriHasSocketProtocol = true;
                    break;
                }
            }

            $this->stream = $uriHasSocketProtocol ? fsockopen($this->uri) : fopen($this->uri, 'wb+');
        }

        if (!is_resource($this->stream) && !$this->stream instanceof Socket) {
            throw new RuntimeException('Cannot initialize a stream.');
        }
    }

    private function writeToStream(string $data): bool
    {
        if ($this->stream === null) {
            return false;
        }

        if ($this->stream instanceof Socket) {
            socket_write($this->stream, $data, strlen($data));

            return true;
        }

        return @fwrite($this->stream, $data) !== false;
    }
}
