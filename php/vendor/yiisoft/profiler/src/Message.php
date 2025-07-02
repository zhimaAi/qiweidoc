<?php

declare(strict_types=1);

namespace Yiisoft\Profiler;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

/**
 * Message is a data object that stores profile message data.
 */
final class Message
{
    /**
     * @param string $level Message level.
     * @param string $token Message token.
     * @param array $context Message context with a following keys:
     *
     * - category: string, message category.
     * - memory: int, memory usage in bytes, obtained by `memory_get_usage()`.
     * - time: float, message timestamp obtained by microtime(true).
     * - trace: array, debug backtrace, contains the application code call stacks.
     *
     * @throws InvalidArgumentException for invalid log message level.
     *
     * @see LoggerTrait::log()
     * @see LogLevel
     */
    public function __construct(private string $level, private string $token, private array $context = [])
    {
    }

    /**
     * Gets a message level.
     *
     * @return string Log message level.
     */
    public function level(): string
    {
        return $this->level;
    }

    /**
     * Gets a message token.
     *
     * @return string Message token.
     */
    public function token(): string
    {
        return $this->token;
    }

    /**
     * Returns a value of the context parameter for the specified name.
     *
     * If no name is specified, the entire context is returned.
     *
     * @param string|null $name The context parameter name.
     * @param mixed $default If the context parameter does not exist, the `$default` will be returned.
     *
     * @psalm-return ($name is null ? array : mixed|null)
     */
    public function context(string $name = null, mixed $default = null): mixed
    {
        if ($name === null) {
            return $this->context;
        }

        /** @var array<array-key, mixed> */
        return $this->context[$name] ?? $default;
    }
}
