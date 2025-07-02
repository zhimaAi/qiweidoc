<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\ObjectFactory;

use ReflectionClass;
use Yiisoft\Hydrator\Exception\AbstractClassException;
use Yiisoft\Hydrator\Exception\NonPublicConstructorException;
use Yiisoft\Hydrator\Exception\WrongConstructorArgumentsCountException;

use function count;

/**
 * A factory for objects that are instantiable by using reflection.
 */
final class ReflectionObjectFactory implements ObjectFactoryInterface
{
    /**
     * @throws AbstractClassException
     * @throws NonPublicConstructorException
     * @throws WrongConstructorArgumentsCountException
     *
     * @psalm-template T of object
     * @psalm-param ReflectionClass<T> $reflectionClass
     * @psalm-return T
     */
    public function create(ReflectionClass $reflectionClass, array $constructorArguments): object
    {
        if ($reflectionClass->isAbstract()) {
            throw new AbstractClassException($reflectionClass);
        }

        $constructor = $reflectionClass->getConstructor();
        if ($constructor !== null) {
            if (!$constructor->isPublic()) {
                throw new NonPublicConstructorException($constructor);
            }

            $countArguments = count($constructorArguments);
            if ($constructor->getNumberOfRequiredParameters() > $countArguments) {
                throw new WrongConstructorArgumentsCountException($constructor, $countArguments);
            }
        }

        return $reflectionClass->newInstance(...$constructorArguments);
    }
}
