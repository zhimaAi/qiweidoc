<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\ObjectFactory;

use ReflectionClass;
use Yiisoft\Hydrator\Exception\NonInstantiableException;

/**
 * An interface for object factory.
 */
interface ObjectFactoryInterface
{
    /**
     * @throws NonInstantiableException
     *
     * @psalm-template T of object
     * @psalm-param ReflectionClass<T> $reflectionClass
     * @psalm-return T
     */
    public function create(ReflectionClass $reflectionClass, array $constructorArguments): object;
}
