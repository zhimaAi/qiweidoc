<?php

declare(strict_types=1);

namespace Yiisoft\Proxy;

use Throwable;

/**
 * Base proxy class for objects to use in {@see ProxyManager}. A concrete implementation can be provided too.
 */
class ObjectProxy
{
    use ProxyTrait;

    public function __construct(
        /**
         * @var object An instance of the class for proxying method calls.
         */
        private object $instance
    ) {
    }

    /**
     * Gets instance.
     *
     * @return object {@see $instance}.
     */
    public function getInstance(): object
    {
        return $this->instance;
    }

    /**
     * Calls a method in the {@see $instance} additionally allowing to process result afterwards (even in case of
     * error).
     *
     * @param string $methodName A called method in the {@see $instance}.
     * @param array $arguments A list of arguments passed to a called method. The order must be maintained.
     *
     * @throws Throwable In case of error happen during the method call.
     *
     * @return $this|mixed Either a new instance of {@see $instance} class or return value of a called method.
     */
    protected function call(string $methodName, array $arguments): mixed
    {
        $this->resetCurrentError();
        $result = null;
        $timeStart = microtime(true);
        try {
            /** @var mixed $result */
            $result = $this->callInternal($methodName, $arguments);
        } catch (Throwable $e) {
            $this->repeatError($e);
        } finally {
            /** @var mixed $result */
            $result = $this->afterCall($methodName, $arguments, $result, $timeStart);
        }

        return $this->processResult($result);
    }

    /**
     * An event executed after each call of a method. Can be used for handling errors, logging, etc. `$result` must be
     * always returned.
     *
     * @param string $methodName A called method in the {@see $instance}.
     * @param array $arguments A list of arguments passed to a called method. The order must be maintained.
     * @param mixed $result Return value of a called method.
     * @param float $timeStart UNIX timestamp right before proxy method call. For example: `1656657586.4849`.
     *
     * @return mixed Return value of a called method.
     */
    protected function afterCall(
        string $methodName,
        array $arguments,
        mixed $result,
        float $timeStart
    ): mixed {
        return $result;
    }

    /**
     * Gets new instance of {@see $instance} class.
     *
     * @param object $instance {@see $instance}.
     *
     * @return $this A new instance of the same class
     */
    protected function getNewStaticInstance(object $instance): self
    {
        /**
         * @psalm-suppress UnsafeInstantiation Constructor should be consistent to `getNewStaticInstance()`.
         */
        return new static($instance);
    }

    /**
     * Just calls a method in the {@see $instance}.
     *
     * @param string $methodName A called method in the {@see $instance}.
     * @param array $arguments A list of arguments passed to a called method. The order must be maintained.
     *
     * @return mixed Return value of a called method.
     */
    private function callInternal(string $methodName, array $arguments): mixed
    {
        /** @psalm-suppress MixedMethodCall */
        return $this->instance->$methodName(...$arguments);
    }

    /**
     * Processes return value of a called method - if it's an instance of the same class in {@see $instance} - a new
     * instance is created, otherwise it's returned as is.
     *
     * @param mixed $result Return value of a called method.
     *
     * @return $this|mixed Either a new instance of {@see $instance} class or return value of a called method.
     */
    private function processResult(mixed $result): mixed
    {
        if (is_object($result) && get_class($result) === get_class($this->instance)) {
            $result = $this->getNewStaticInstance($result);
        }

        return $result;
    }
}
