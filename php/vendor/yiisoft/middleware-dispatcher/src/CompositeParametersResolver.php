<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Psr\Http\Message\ServerRequestInterface;

use function in_array;

final class CompositeParametersResolver implements ParametersResolverInterface
{
    /**
     * @var ParametersResolverInterface[]
     */
    private array $resolvers;

    public function __construct(ParametersResolverInterface ...$resolvers)
    {
        $this->resolvers = $resolvers;
    }

    public function resolve(array $parameters, ServerRequestInterface $request): array
    {
        $results = [];
        foreach ($this->resolvers as $resolver) {
            $result = $resolver->resolve($parameters, $request);
            $results[] = $result;

            $resultKeys = array_keys($result);
            $parameters = array_filter(
                $parameters,
                static fn($key) => !in_array($key, $resultKeys, true),
                ARRAY_FILTER_USE_KEY
            );
        }

        return array_merge(...$results);
    }
}
