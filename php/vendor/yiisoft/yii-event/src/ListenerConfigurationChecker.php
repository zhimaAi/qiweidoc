<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event;

use Psr\Container\ContainerExceptionInterface;

use function is_array;
use function is_object;
use function is_string;

/**
 * `ListenerConfigurationChecker` could be used in development mode to check if listeners are defined correctly.
 *
 * ```php
 * $checker->check($configuration->get('events-web'));
 * ```
 */
final class ListenerConfigurationChecker
{
    public function __construct(
        private CallableFactory $callableFactory,
    ) {
    }

    /**
     * Checks the given event configuration and throws an exception in some cases:
     * - incorrect configuration format
     * - incorrect listener format
     * - listener is not a callable
     * - listener is meant to be a method of an object which can't be instantiated
     *
     * @param array $configuration An array in format of [eventClassName => [listeners]]
     */
    public function check(array $configuration): void
    {
        foreach ($configuration as $eventName => $listeners) {
            if (!is_string($eventName) || !class_exists($eventName)) {
                throw new InvalidEventConfigurationFormatException(
                    'Incorrect event listener format. Format with event name must be used. Got ' .
                    var_export($eventName, true) . '.'
                );
            }

            if (!is_iterable($listeners)) {
                $type = get_debug_type($listeners);

                throw new InvalidEventConfigurationFormatException(
                    "Event listeners for $eventName must be an iterable, $type given."
                );
            }

            /** @var mixed */
            foreach ($listeners as $listener) {
                try {
                    if (!$this->isCallable($listener)) {
                        throw new InvalidListenerConfigurationException(
                            $this->createNotCallableMessage($listener)
                        );
                    }
                } catch (ContainerExceptionInterface $exception) {
                    throw new InvalidListenerConfigurationException(
                        'Could not instantiate event listener or listener class has invalid configuration. Got ' .
                        $this->listenerDump($listener) . '.',
                        0,
                        $exception
                    );
                }
            }
        }
    }

    private function createNotCallableMessage(mixed $definition): string
    {
        if (is_string($definition) && class_exists($definition)) {
            if (!method_exists($definition, '__invoke')) {
                return sprintf(
                    '"__invoke" method is not defined in "%s" class.',
                    $definition
                );
            }

            return sprintf(
                'Failed to instantiate "%s" class.',
                $definition
            );
        }

        if (is_array($definition)
            && array_keys($definition) === [0, 1]
            && is_string($definition[1])
        ) {
            if (is_string($definition[0]) && class_exists($definition[0])) {
                return sprintf(
                    'Could not instantiate "%s" or "%s" method is not defined in this class.',
                    $definition[0],
                    $definition[1],
                );
            }
            if (is_object($definition[0])) {
                return sprintf(
                    '"%s" method is not defined in "%s" class.',
                    $definition[1],
                    $definition[0]::class,
                );
            }
        }

        return 'Listener must be a callable. Got ' . $this->listenerDump($definition) . '.';
    }

    /**
     * @throws ContainerExceptionInterface Error while retrieving the entry from container.
     */
    private function isCallable(mixed $definition): bool
    {
        try {
            $this->callableFactory->create($definition);
        } catch (InvalidListenerConfigurationException) {
            return false;
        }

        return true;
    }

    private function listenerDump(mixed $listener): string
    {
        return is_object($listener) ? $listener::class : var_export($listener, true);
    }
}
