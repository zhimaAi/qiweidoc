<?php

declare(strict_types=1);

namespace RoadRunner\Logger;

use RoadRunner\AppLogger\DTO\V1\LogAttrs;
use RoadRunner\AppLogger\DTO\V1\LogEntry;
use RoadRunner\Logger\Exception\LoggerException;
use Spiral\Goridge\RPC\Codec\ProtobufCodec;
use Spiral\Goridge\RPC\CodecInterface;
use Spiral\Goridge\RPC\Exception\ServiceException;
use Spiral\Goridge\RPC\RPCInterface;

/**
 * @psalm-type TMessage = string|\Stringable
 * @psalm-type TContext = array<string, mixed>
 */
final class Logger
{
    private readonly RPCInterface $rpc;
    private readonly CodecInterface $codec;

    public function __construct(RPCInterface $rpc)
    {
        $this->rpc = $rpc->withServicePrefix('app');
        $this->codec = new ProtobufCodec();
    }

    /**
     * Log an emergency message to the logs.
     *
     * @param TMessage $message
     * @param TContext $context
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->send(LogLevel::Error, $message, $context);
    }

    /**
     * Log a warning message to the logs.
     *
     * @param TMessage $message
     * @param TContext $context
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->send(LogLevel::Warning, $message, $context);
    }

    /**
     * Log an informational message to the logs.
     *
     * @param TMessage $message
     * @param TContext $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->send(LogLevel::Info, $message, $context);
    }

    /**
     * Log a debug message to the logs.
     *
     * @param TMessage $message
     * @param TContext $context
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->send(LogLevel::Debug, $message, $context);
    }

    /**
     * Log a message to the logs.
     *
     * @param TMessage $message
     * @param TContext $context
     */
    public function log(string|\Stringable $message, array $context = []): void
    {
        $this->send(LogLevel::Log, $message, $context);
    }

    /**
     * @param TContext $context
     * @throws LoggerException
     */
    private function send(LogLevel $level, string|\Stringable $message, array $context = []): void
    {
        try {
            if ($context === []) {
                $this->rpc->call($level->name, (string)$message);
            } else {
                $attrs = [];

                foreach ($context as $key => $value) {
                    try {
                        $attrs[] = new LogAttrs(data: [
                            'key' => $key,
                            'value' => $this->prepareValue($value),
                        ]);
                    } catch (\Throwable) {
                        // We can't log this value, so we just skip it.
                    }
                }

                $level = $level->name . 'WithContext';
                $this->rpc
                    ->withCodec($this->codec)
                    ->call(
                        $level,
                        new LogEntry([
                            'message' => (string)$message,
                            'log_attrs' => $attrs,
                        ]),
                    );
            }
        } catch (ServiceException $e) {
            $this->handleError($e);
        }
    }

    /**
     * @throws Exception\LoggerException
     */
    private function handleError(ServiceException $e): never
    {
        $message = \str_replace(["\t", "\n"], ' ', $e->getMessage());

        throw new Exception\LoggerException($message, $e->getCode(), $e);
    }

    /**
     * @throws \Throwable
     */
    private function prepareValue(mixed $value): string
    {
        if (\is_string($value)) {
            return $value;
        }

        if ($value instanceof \Stringable) {
            return (string)$value;
        }

        return \json_encode($value, JSON_THROW_ON_ERROR);
    }
}
