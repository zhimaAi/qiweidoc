<?php

declare(strict_types=1);

namespace Yiisoft\Proxy;

use InvalidArgumentException;
use Reflection;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use Yiisoft\Proxy\Config\ClassConfig;
use Yiisoft\Proxy\Config\MethodConfig;
use Yiisoft\Proxy\Config\ParameterConfig;
use Yiisoft\Proxy\Config\TypeConfig;

/**
 * @internal
 *
 * A factory for creating class configs ({@see ClassConfig}). Uses PHP `Reflection` to get the necessary metadata.
 *
 * @link https://www.php.net/manual/en/book.reflection.php
 */
final class ClassConfigFactory
{
    /**
     * Gets single class config based for individual class.
     *
     * @param string $className Full class or interface name (including namespace).
     *
     * @psalm-param class-string $className
     *
     * @throws InvalidArgumentException In case class or interface does not exist.
     *
     * @return ClassConfig Class config with all related configs (methods, parameters, types) linked.
     */
    public function getClassConfig(string $className): ClassConfig
    {
        try {
            $reflection = new ReflectionClass($className);
        } catch (ReflectionException) {
            throw new InvalidArgumentException("$className must exist.");
        }

        /**
         * @psalm-suppress MixedArgumentTypeCoercion Can be removed after release
         * https://github.com/vimeo/psalm/pull/8405
         */
        return new ClassConfig(
            isInterface: $reflection->isInterface(),
            namespace: $reflection->getNamespaceName(),
            modifiers: Reflection::getModifierNames($reflection->getModifiers()),
            name: $reflection->getName(),
            shortName: $reflection->getShortName(),
            parent: (string) $reflection->getParentClass(),
            interfaces: $reflection->getInterfaceNames(),
            methods: $this->getMethodConfigs($reflection),
        );
    }

    /**
     * Gets the complete set of method configs for a given class reflection.
     *
     * @param ReflectionClass $class Reflection of a class.
     *
     * @return MethodConfig[] List of method configs. The order is maintained.
     * @psalm-return array<string,MethodConfig>
     */
    private function getMethodConfigs(ReflectionClass $class): array
    {
        $methods = [];
        foreach ($class->getMethods() as $method) {
            $methods[$method->getName()] = $this->getMethodConfig($class, $method);
        }

        return $methods;
    }

    /**
     * Gets single method config for individual class / method reflection pair.
     *
     * @param ReflectionClass $class Reflection of a class.
     * @param ReflectionMethod $method Reflection of a method.
     *
     * @return MethodConfig Single method config.
     */
    private function getMethodConfig(ReflectionClass $class, ReflectionMethod $method): MethodConfig
    {
        return new MethodConfig(
            modifiers: $this->getMethodModifiers($class, $method),
            name: $method->getName(),
            parameters: $this->getMethodParameterConfigs($method),
            returnType: $this->getMethodReturnTypeConfig($method),
        );
    }

    /**
     * Gets the set of method modifiers for a given class / method reflection pair
     *
     * @param ReflectionClass $class Reflection of a class.
     * @param ReflectionMethod $method Reflection of a method.
     *
     * @return string[] List of method modifiers.
     */
    private function getMethodModifiers(ReflectionClass $class, ReflectionMethod $method): array
    {
        $modifiers = Reflection::getModifierNames($method->getModifiers());
        if (!$class->isInterface()) {
            return $modifiers;
        }

        return array_values(
            array_filter(
                $modifiers,
                static fn (string $modifier) => $modifier !== 'abstract'
            )
        );
    }

    /**
     * Gets the complete set of parameter configs for a given method reflection.
     *
     * @param ReflectionMethod $method Reflection of a method.
     *
     * @return ParameterConfig[] List of parameter configs. The order is maintained.
     * @psalm-return array<string,ParameterConfig>
     */
    private function getMethodParameterConfigs(ReflectionMethod $method): array
    {
        $parameters = [];
        foreach ($method->getParameters() as $param) {
            $parameters[$param->getName()] = $this->getMethodParameterConfig($param);
        }

        return $parameters;
    }

    /**
     * Gets single parameter config for individual method's parameter reflection.
     *
     * @param ReflectionParameter $param Reflection of a method's parameter.
     *
     * @return ParameterConfig Single parameter config.
     */
    private function getMethodParameterConfig(ReflectionParameter $param): ParameterConfig
    {
        return new ParameterConfig(
            type: $this->getMethodParameterTypeConfig($param),
            name: $param->getName(),
            isDefaultValueAvailable: $param->isDefaultValueAvailable(),
            isDefaultValueConstant: $param->isDefaultValueAvailable()
                ? $param->isDefaultValueConstant()
                : null,
            defaultValueConstantName: $param->isOptional()
                ? $param->getDefaultValueConstantName()
                : null,
            defaultValue: $param->isOptional()
                ? $param->getDefaultValue()
                : null,
        );
    }

    /**
     * Gets single type config for individual method's parameter reflection.
     *
     * @param ReflectionParameter $param Reflection pf a method's parameter.
     *
     * @return TypeConfig|null Single type config. `null` is returned when type is not specified.
     */
    private function getMethodParameterTypeConfig(ReflectionParameter $param): ?TypeConfig
    {
        /**
         * @var ReflectionIntersectionType|ReflectionNamedType|ReflectionUnionType|null $type
         * @psalm-suppress UndefinedDocblockClass Needed for PHP 8.0 only, because ReflectionIntersectionType is
         * not supported.
         */
        $type = $param->getType();
        if (!$type) {
            return null;
        }

        /**
         * @psalm-suppress UndefinedClass Needed for PHP 8.0 only, because ReflectionIntersectionType is not supported.
         */
        return new TypeConfig(
            name: $this->convertTypeToString($type),
            allowsNull: $type->allowsNull(),
        );
    }

    /**
     * Gets single return type config for individual method reflection.
     *
     * @param ReflectionMethod $method Reflection of a method.
     *
     * @return TypeConfig|null Single type config. `null` is returned when return type is not specified.
     */
    private function getMethodReturnTypeConfig(ReflectionMethod $method): ?TypeConfig
    {
        $returnType = $method->getReturnType();
        if (!$returnType && method_exists($method, 'getTentativeReturnType')) {
            /**
             * Needed for PHP 8.0 only, because getTentativeReturnType() is not supported.
             *
             * @var ReflectionType|null
             * @psalm-suppress UnnecessaryVarAnnotation
             */
            $returnType = $method->getTentativeReturnType();
        }

        if (!$returnType) {
            return null;
        }

        /**
         * @psalm-suppress ArgumentTypeCoercion Needed for PHP 8.0 only, because ReflectionIntersectionType is
         * not supported.
         */
        return new TypeConfig(
            name: $this->convertTypeToString($returnType),
            allowsNull: $returnType->allowsNull(),
        );
    }

    /**
     * @psalm-suppress UndefinedClass Needed for PHP 8.0 only, because ReflectionIntersectionType is not supported.
     */
    private function convertTypeToString(
        ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType $type
    ): string {
        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }

        if ($type instanceof ReflectionUnionType) {
            return $this->getUnionType($type);
        }

        return $this->getIntersectionType($type);
    }

    private function getUnionType(ReflectionUnionType $type): string
    {
        $types = array_map(
            static fn (ReflectionNamedType $namedType) => $namedType->getName(),
            $type->getTypes()
        );

        return implode('|', $types);
    }

    /**
     * @psalm-suppress UndefinedClass, MixedArgument Needed for PHP 8.0 only, because ReflectionIntersectionType is
     * not supported.
     */
    private function getIntersectionType(ReflectionIntersectionType $type): string
    {
        /**
         * @psalm-suppress ArgumentTypeCoercion ReflectionIntersectionType::getTypes() always returns
         * array of `ReflectionNamedType`, at least until PHP 8.2 released.
         */
        $types = array_map(
            static fn (ReflectionNamedType $namedType) => $namedType->getName(),
            $type->getTypes()
        );

        return implode('&', $types);
    }
}
