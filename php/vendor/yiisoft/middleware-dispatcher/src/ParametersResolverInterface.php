<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Psr\Http\Message\ServerRequestInterface;
use ReflectionParameter;

/**
 * Resolves parameters of PSR-15 middleware that are provided as callable.
 * You may implement this interface if you want to introduce custom dependencies or inject additional data from
 * the {@see ServerRequestInterface} (e.g. using attributes) to the middleware.
 */
interface ParametersResolverInterface
{
    /**
     * Resolve parameters of a PSR-15 middleware the provided as callable.
     *
     * @param ReflectionParameter[] $parameters
     *
     * @psalm-param array<string,ReflectionParameter> $parameters
     *
     * @psalm-return array<string,mixed>
     */
    public function resolve(array $parameters, ServerRequestInterface $request): array;
}
