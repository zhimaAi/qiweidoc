<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Helpers;

use ReflectionClass;
use ReflectionException;
use ReflectionFunctionAbstract;
use Yiisoft\Definitions\Exception\NotInstantiableClassException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\ParameterDefinition;

/**
 * This class extracts dependency definitions from type hints of a function or a class constructor parameters.
 * Note that service names need not match the parameter names, parameter names are ignored.
 *
 * @internal
 */
final class DefinitionExtractor
{
    /**
     * @psalm-var array<string, array<string, ParameterDefinition>>
     */
    private static array $dependencies = [];

    /**
     * Extract dependency definitions from type hints of a class constructor parameters.
     *
     * @psalm-param class-string $class
     *
     * @throws NotInstantiableException
     *
     * @return ParameterDefinition[]
     * @psalm-return array<string, ParameterDefinition>
     */
    public static function fromClassName(string $class): array
    {
        if (isset(self::$dependencies[$class])) {
            return self::$dependencies[$class];
        }

        try {
            $reflectionClass = new ReflectionClass($class);
        } catch (ReflectionException) {
            throw new NotInstantiableClassException($class);
        }

        if (!$reflectionClass->isInstantiable()) {
            throw new NotInstantiableClassException($class);
        }

        $constructor = $reflectionClass->getConstructor();
        $dependencies = $constructor === null ? [] : self::fromFunction($constructor);
        self::$dependencies[$class] = $dependencies;

        return $dependencies;
    }

    /**
     * Extract dependency definitions from type hints of a function.
     *
     * @return ParameterDefinition[]
     * @psalm-return array<string, ParameterDefinition>
     */
    public static function fromFunction(ReflectionFunctionAbstract $reflectionFunction): array
    {
        $result = [];
        foreach ($reflectionFunction->getParameters() as $parameter) {
            $result[$parameter->getName()] = new ParameterDefinition($parameter);
        }
        return $result;
    }
}
