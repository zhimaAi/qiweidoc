<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionMethod;

use function is_array;
use function is_callable;
use function is_string;

/**
 * @internal Create real callable listener from configuration.
 */
final class CallableFactory
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    /**
     * Create real callable listener from definition.
     *
     * @param mixed $definition Definition to create listener from.
     *
     * @throws InvalidListenerConfigurationException Failed to create listener.
     * @throws ContainerExceptionInterface Error while retrieving the entry from container.
     */
    public function create(mixed $definition): callable
    {
        /** @var mixed */
        $callable = $this->prepare($definition);

        if (is_callable($callable)) {
            return $callable;
        }

        throw new InvalidListenerConfigurationException();
    }

    /**
     * @throws ContainerExceptionInterface Error while retrieving the entry from container.
     *
     * @return mixed
     */
    private function prepare(mixed $definition)
    {
        if (is_string($definition) && $this->container->has($definition)) {
            return $this->container->get($definition);
        }

        if (is_array($definition)
            && array_keys($definition) === [0, 1]
            && is_string($definition[0])
            && is_string($definition[1])
        ) {
            [$className, $methodName] = $definition;

            if (!class_exists($className) && $this->container->has($className)) {
                /** @var mixed */
                return [
                    $this->container->get($className),
                    $methodName,
                ];
            }

            if (!class_exists($className)) {
                return null;
            }

            try {
                $reflection = new ReflectionMethod($className, $methodName);
            } catch (ReflectionException) {
                return null;
            }
            if ($reflection->isStatic()) {
                return [$className, $methodName];
            }
            if ($this->container->has($className)) {
                return [
                    $this->container->get($className),
                    $methodName,
                ];
            }
            return null;
        }

        return $definition;
    }
}
