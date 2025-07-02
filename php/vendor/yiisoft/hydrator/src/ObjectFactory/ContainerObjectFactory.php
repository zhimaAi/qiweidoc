<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\ObjectFactory;

use Psr\Container\ContainerExceptionInterface;
use ReflectionClass;
use ReflectionException;
use Yiisoft\Injector\Injector;

/**
 * A factory for objects that are instantiable by a container.
 */
final class ContainerObjectFactory implements ObjectFactoryInterface
{
    public function __construct(
        private Injector $injector,
    ) {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     *
     * @psalm-template T of object
     * @psalm-param ReflectionClass<T> $reflectionClass
     * @psalm-return T
     */
    public function create(ReflectionClass $reflectionClass, array $constructorArguments): object
    {
        $class = $reflectionClass->getName();
        return $this->injector->make($class, $constructorArguments);
    }
}
