<?php

declare(strict_types=1);

namespace Yiisoft\VarDumper\Handler;

use Yiisoft\VarDumper\HandlerInterface;

/**
 * `CompositeHandler` allows to use multiple handlers at once.
 * It iterates over all handlers and calls their {@see HandlerInterface::handle()} method.
 * For example, you may use it to output data to both {@see StreamHandler} and {@see EchoHandler} at once.
 */
final class CompositeHandler implements HandlerInterface
{
    /**
     * @param HandlerInterface[] $handlers
     */
    public function __construct(
        private array $handlers
    ) {
    }

    public function handle(mixed $variable, int $depth, bool $highlight = false): void
    {
        foreach ($this->handlers as $handler) {
            $handler->handle($variable, $depth, $highlight);
        }
    }
}
