<?php

declare(strict_types=1);

namespace Yiisoft\Input\Http;

use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Yiisoft\Hydrator\HydratorInterface;
use Yiisoft\Hydrator\Validator\ValidatedInputInterface;
use Yiisoft\Middleware\Dispatcher\ParametersResolverInterface;

/**
 * Resolves request input parameters.
 */
final class RequestInputParametersResolver implements ParametersResolverInterface
{
    /**
     * @param HydratorInterface $hydrator The hydrator to use.
     * @param bool $throwInputValidationException Whether to throw an exception if input validation fails.
     */
    public function __construct(
        private HydratorInterface $hydrator,
        private bool $throwInputValidationException = false,
    ) {
    }

    /**
     * @param ReflectionParameter[] $parameters The parameters to resolve.
     * @param ServerRequestInterface $request The request to get input from.
     *
     * @throws InputValidationException If input validation fails.
     *
     * @return RequestInputInterface[] The resolved parameters.
     *
     * @psalm-param array<string,ReflectionParameter> $parameters
     * @psalm-return array<string,RequestInputInterface>
     */
    public function resolve(array $parameters, ServerRequestInterface $request): array
    {
        $result = [];
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $class = $type->getName();

            $reflectionClass = new ReflectionClass($class);
            if (
                $reflectionClass->isInstantiable()
                && $reflectionClass->implementsInterface(RequestInputInterface::class)
            ) {
                /** @psalm-var class-string<RequestInputInterface> $class */
                $value = $this->hydrator->create($class);
                $this->processValidation($value);
                $result[$parameter->getName()] = $value;
            }
        }
        return $result;
    }

    /**
     * Validates input.
     *
     * @throws InputValidationException If input validation fails.
     */
    private function processValidation(mixed $value): void
    {
        if (
            !$this->throwInputValidationException
            || !$value instanceof ValidatedInputInterface
        ) {
            return;
        }

        $result = $value->getValidationResult();
        if (!$result->isValid()) {
            throw new InputValidationException($result);
        }
    }
}
