<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Yiisoft\Http\Method;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;

final class MatchingResult implements MiddlewareInterface
{
    /**
     * @var array<string,string>
     */
    private array $arguments = [];

    /**
     * @var string[]
     */
    private array $methods = [];

    private ?MiddlewareDispatcher $dispatcher = null;

    private function __construct(private ?Route $route)
    {
    }

    public function withDispatcher(MiddlewareDispatcher $dispatcher): self
    {
        $new = clone $this;
        $new->dispatcher = $dispatcher;
        return $new;
    }

    /**
     * @param array<string,string> $arguments
     */
    public static function fromSuccess(Route $route, array $arguments): self
    {
        $new = new self($route);
        $new->arguments = $arguments;
        return $new;
    }

    /**
     * @param string[] $methods
     */
    public static function fromFailure(array $methods): self
    {
        $new = new self(null);
        $new->methods = $methods;
        return $new;
    }

    /**
     * @psalm-assert-if-true !null $this->route
     */
    public function isSuccess(): bool
    {
        return $this->route !== null;
    }

    public function isMethodFailure(): bool
    {
        return $this->route === null && $this->methods !== Method::ALL;
    }

    /**
     * @return array<string,string>
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return string[]
     */
    public function methods(): array
    {
        return $this->methods;
    }

    public function route(): Route
    {
        if ($this->route === null) {
            throw new RuntimeException('There is no route in the matching result.');
        }

        return $this->route;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isSuccess()) {
            return $handler->handle($request);
        }

        // Inject dispatcher only if we have not previously injected.
        // This improves performance in event-loop applications.
        if ($this->dispatcher !== null && !$this->route->getData('hasDispatcher')) {
            $this->route->injectDispatcher($this->dispatcher);
        }

        return $this->route
            ->getData('dispatcherWithMiddlewares')
            ->dispatch($request, $handler);
    }
}
