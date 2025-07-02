<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Contract\ReferenceInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Helpers\ArrayDefinitionHelper;
use Yiisoft\Definitions\Helpers\DefinitionExtractor;
use Yiisoft\Definitions\Helpers\DefinitionResolver;

use function array_key_exists;
use function call_user_func_array;
use function count;
use function gettype;
use function is_array;
use function is_string;
use function sprintf;

/**
 * Builds an object by array config.
 *
 * @psalm-type MethodOrPropertyItem = array{0:string,1:string,2:mixed}
 * @psalm-type ArrayDefinitionConfig = array{class:class-string,'__construct()'?:array}&array<string, mixed>
 */
final class ArrayDefinition implements DefinitionInterface
{
    public const CLASS_NAME = 'class';
    public const CONSTRUCTOR = '__construct()';

    public const TYPE_PROPERTY = 'property';
    public const TYPE_METHOD = 'method';

    /**
     * Container used to resolve references.
     */
    private ?ContainerInterface $referenceContainer = null;

    /**
     * @psalm-param class-string $class
     * @psalm-param array<string, MethodOrPropertyItem> $methodsAndProperties
     */
    private function __construct(
        private string $class,
        private array $constructorArguments,
        private array $methodsAndProperties,
    ) {}

    /**
     * @param ContainerInterface|null $referenceContainer Container to resolve references with.
     */
    public function withReferenceContainer(?ContainerInterface $referenceContainer): self
    {
        $new = clone $this;
        $new->referenceContainer = $referenceContainer;
        return $new;
    }

    /**
     * Create ArrayDefinition from array config.
     *
     * @psalm-param ArrayDefinitionConfig $config
     */
    public static function fromConfig(array $config): self
    {
        return new self(
            $config[self::CLASS_NAME],
            $config[self::CONSTRUCTOR] ?? [],
            self::getMethodsAndPropertiesFromConfig($config),
        );
    }

    /**
     * @psalm-param class-string $class
     * @psalm-param array<string, MethodOrPropertyItem> $methodsAndProperties
     */
    public static function fromPreparedData(string $class, array $constructorArguments = [], array $methodsAndProperties = []): self
    {
        return new self($class, $constructorArguments, $methodsAndProperties);
    }

    /**
     * @psalm-param array<string, mixed> $config
     *
     * @psalm-return array<string, MethodOrPropertyItem>
     */
    private static function getMethodsAndPropertiesFromConfig(array $config): array
    {
        $methodsAndProperties = [];

        foreach ($config as $key => $value) {
            if ($key === self::CONSTRUCTOR) {
                continue;
            }

            /**
             * @infection-ignore-all Explode limit does not affect the result.
             *
             * @see \Yiisoft\Definitions\Tests\Unit\Helpers\DefinitionValidatorTest::testIncorrectMethodName()
             */
            if (count($methodArray = explode('()', $key, 2)) === 2) {
                $methodsAndProperties[$key] = [self::TYPE_METHOD, $methodArray[0], $value];
            } elseif (count($propertyArray = explode('$', $key)) === 2) {
                $methodsAndProperties[$key] = [self::TYPE_PROPERTY, $propertyArray[1], $value];
            }
        }

        return $methodsAndProperties;
    }

    /**
     * @psalm-return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    public function getConstructorArguments(): array
    {
        return $this->constructorArguments;
    }

    /**
     * @psalm-return array<string, MethodOrPropertyItem>
     */
    public function getMethodsAndProperties(): array
    {
        return $this->methodsAndProperties;
    }

    public function resolve(ContainerInterface $container): object
    {
        $class = $this->class;

        $resolvedConstructorArguments = $this->resolveFunctionArguments(
            $container,
            DefinitionExtractor::fromClassName($class),
            $this->getConstructorArguments(),
        );

        /** @psalm-suppress MixedMethodCall */
        $object = new $class(...$resolvedConstructorArguments);

        foreach ($this->getMethodsAndProperties() as $item) {
            [$type, $name, $value] = $item;
            if ($type === self::TYPE_METHOD) {
                /** @var array $value */
                if (method_exists($object, $name)) {
                    $resolvedMethodArguments = $this->resolveFunctionArguments(
                        $container,
                        DefinitionExtractor::fromFunction(new ReflectionMethod($object, $name)),
                        $value,
                    );
                } else {
                    $resolvedMethodArguments = $value;
                }
                $setter = call_user_func_array([$object, $name], $resolvedMethodArguments);
                if ($setter instanceof $object) {
                    /** @var object $object */
                    $object = $setter;
                }
            } elseif ($type === self::TYPE_PROPERTY) {
                $object->$name = DefinitionResolver::resolve($container, $this->referenceContainer, $value);
            }
        }

        return $object;
    }

    /**
     * @param array<string,ParameterDefinition> $dependencies
     *
     * @psalm-return list<mixed>
     */
    private function resolveFunctionArguments(
        ContainerInterface $container,
        array $dependencies,
        array $arguments,
    ): array {
        $isIntegerIndexed = $this->isIntegerIndexed($arguments);
        $dependencyIndex = 0;
        $usedArguments = [];
        $variadicKey = null;

        foreach ($dependencies as $key => &$value) {
            if ($value->isVariadic()) {
                $variadicKey = $key;
            }
            $index = $isIntegerIndexed ? $dependencyIndex : $key;
            if (array_key_exists($index, $arguments)) {
                $value = DefinitionResolver::ensureResolvable($arguments[$index]);
                /** @infection-ignore-all Mutation don't change behaviour. Values of `$usedArguments` not used. */
                $usedArguments[$index] = 1;
            }
            $dependencyIndex++;
        }
        unset($value);

        if ($variadicKey !== null) {
            if (!$isIntegerIndexed && isset($arguments[$variadicKey])) {
                if ($arguments[$variadicKey] instanceof ReferenceInterface) {
                    $arguments[$variadicKey] = DefinitionResolver::resolve(
                        $container,
                        $this->referenceContainer,
                        $arguments[$variadicKey],
                    );
                }

                if (is_array($arguments[$variadicKey])) {
                    unset($dependencies[$variadicKey]);
                    $dependencies += $arguments[$variadicKey];
                } else {
                    throw new InvalidArgumentException(
                        sprintf(
                            'Named argument for a variadic parameter should be an array, "%s" given.',
                            gettype($arguments[$variadicKey]),
                        ),
                    );
                }
            } else {
                foreach ($arguments as $index => $value) {
                    if (!isset($usedArguments[$index])) {
                        $dependencies[$index] = DefinitionResolver::ensureResolvable($value);
                    }
                }
            }
        }

        $resolvedArguments = DefinitionResolver::resolveArray($container, $this->referenceContainer, $dependencies);
        return array_values($resolvedArguments);
    }

    /**
     * @throws InvalidConfigException
     */
    private function isIntegerIndexed(array $arguments): bool
    {
        $hasStringIndex = false;
        $hasIntegerIndex = false;

        foreach ($arguments as $index => $_argument) {
            if (is_string($index)) {
                $hasStringIndex = true;
                if ($hasIntegerIndex) {
                    /** @infection-ignore-all Mutation don't change behaviour, but degrade performance. */
                    break;
                }
            } else {
                $hasIntegerIndex = true;
                if ($hasStringIndex) {
                    /** @infection-ignore-all Mutation don't change behaviour, but degrade performance. */
                    break;
                }
            }
        }
        if ($hasIntegerIndex && $hasStringIndex) {
            throw new InvalidConfigException(
                'Arguments indexed both by name and by position are not allowed in the same array.',
            );
        }

        return $hasIntegerIndex;
    }

    /**
     * Create a new definition that is merged from this definition and another definition.
     *
     * @param ArrayDefinition $other Definition to merge with.
     *
     * @return self New definition that is merged from this definition and another definition.
     */
    public function merge(self $other): self
    {
        $new = clone $this;
        $new->class = $other->class;
        $new->constructorArguments = ArrayDefinitionHelper::mergeArguments($this->constructorArguments, $other->constructorArguments);

        $methodsAndProperties = $this->methodsAndProperties;
        foreach ($other->methodsAndProperties as $key => $item) {
            if ($item[0] === self::TYPE_PROPERTY) {
                $methodsAndProperties[$key] = $item;
            } elseif ($item[0] === self::TYPE_METHOD) {
                /** @psalm-suppress MixedArgument */
                $arguments = isset($methodsAndProperties[$key])
                    ? ArrayDefinitionHelper::mergeArguments($methodsAndProperties[$key][2], $item[2])
                    : $item[2];
                $methodsAndProperties[$key] = [$item[0], $item[1], $arguments];
            }
        }
        $new->methodsAndProperties = $methodsAndProperties;

        return $new;
    }
}
