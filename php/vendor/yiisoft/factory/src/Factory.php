<?php

declare(strict_types=1);

namespace Yiisoft\Factory;

use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Helpers\DefinitionValidator;
use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\Helpers\Normalizer;

use function is_string;

/**
 * Factory allows creating objects passing arguments runtime.
 * A factory will try to use a PSR-11 compliant container to get dependencies, but will fall back to manual
 * instantiation if the container cannot provide a required dependency.
 */
final class Factory
{
    private FactoryInternalContainer $internalContainer;

    /**
     * @param ContainerInterface|null $container Container to use for resolving dependencies.
     * @param array<string, mixed> $definitions Definitions to create objects with.
     * @param bool $validate If definitions should be validated when set.
     *
     * @throws InvalidConfigException
     */
    public function __construct(
        ?ContainerInterface $container = null,
        array $definitions = [],
        private bool $validate = true
    ) {
        $this->validateDefinitions($definitions);
        $this->internalContainer = new FactoryInternalContainer($container, $definitions);
    }

    /**
     * @param array<string, mixed> $definitions Definitions to create objects with.
     *
     * @throws InvalidConfigException
     */
    public function withDefinitions(array $definitions): self
    {
        $this->validateDefinitions($definitions);

        $new = clone $this;
        $new->internalContainer = $this->internalContainer->withDefinitions($definitions);
        return $new;
    }

    /**
     * @param array $definitions Definitions to validate.
     * @psalm-param array<string, mixed> $definitions
     *
     * @throws InvalidConfigException
     */
    private function validateDefinitions(array $definitions): void
    {
        if ($this->validate) {
            foreach ($definitions as $id => $definition) {
                DefinitionValidator::validate($definition, $id);
            }
        }
    }

    /**
     * Creates a new object using the given configuration.
     *
     * You may view this method as an enhanced version of the `new` operator.
     * The method supports creating an object based on a class name, a configuration array or
     * an anonymous function.
     *
     * Below are some usage examples:
     *
     * ```php
     * // create an object using a class name
     * $object = $factory->create(\Yiisoft\Db\Connection::class);
     *
     * // create an object using a configuration array
     * $object = $factory->create([
     *     'class' => \Yiisoft\Db\Connection\Connection::class,
     *     '__construct()' => [
     *         'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     ],
     *     'setUsername()' => ['root'],
     *     'setPassword()' => [''],
     *     'setCharset()' => ['utf8'],
     * ]);
     * ```
     *
     * Using [[Container|dependency injection container]], this method can also identify
     * dependent objects, instantiate them and inject them into the newly created object.
     *
     * @param mixed $config The object configuration. This can be specified in one of the following forms:
     *
     * - A string: representing the class name of the object to be created.
     *
     * - A configuration array: the array must contain a `class` element which is treated as the object class,
     *   and the rest of the name-value pairs will be used to initialize the corresponding object properties.
     *
     * - A PHP callable: either an anonymous function or an array representing a class method
     *   (`[$class or $object, $method]`). The callable should return a new instance of the object being created.
     *
     * @throws InvalidConfigException If the configuration is invalid.
     * @throws CircularReferenceException
     * @throws NotFoundException
     * @throws NotInstantiableException
     *
     * @return mixed|object The created object.
     *
     * @psalm-template T
     * @psalm-param mixed|class-string<T> $config
     * @psalm-return ($config is class-string ? T : mixed)
     * @psalm-suppress MixedReturnStatement
     */
    public function create(mixed $config): mixed
    {
        if ($this->validate) {
            DefinitionValidator::validate($config);
        }

        if (is_string($config)) {
            if ($this->internalContainer->hasDefinition($config)) {
                $definition = $this->internalContainer->getDefinition($config);
            } elseif (class_exists($config)) {
                $definition = ArrayDefinition::fromPreparedData($config);
            } else {
                throw new NotFoundException($config);
            }
        } else {
            $definition = $this->createDefinition($config);
        }

        return $this->internalContainer->create($definition);
    }

    /**
     * @throws InvalidConfigException
     */
    private function createDefinition(mixed $config): DefinitionInterface
    {
        $definition = Normalizer::normalize($config);

        if (
            ($definition instanceof ArrayDefinition)
            && $this->internalContainer->hasDefinition($definition->getClass())
            && ($containerDefinition = $this->internalContainer->getDefinition($definition->getClass()))
            instanceof ArrayDefinition
        ) {
            $definition = $this->mergeDefinitions(
                $containerDefinition,
                $definition
            );
        }

        return $definition;
    }

    private function mergeDefinitions(DefinitionInterface $one, ArrayDefinition $two): DefinitionInterface
    {
        return $one instanceof ArrayDefinition ? $one->merge($two) : $two;
    }
}
