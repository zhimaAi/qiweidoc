<?php

declare(strict_types=1);

namespace Yiisoft\Proxy;

use Throwable;

/**
 * @internal
 *
 * This trait allows to handle errors during proxy method calls. Handling can be added in
 * {@see ObjectProxy::afterCall()} event.
 */
trait ProxyTrait
{
    /**
     * @var Throwable|null A throwable object extracted from exception thrown during the last proxy method call. It's
     * `null` when no exception was thrown. Automatically reset during the new call.
     */
    private ?Throwable $currentError = null;

    /**
     * Gets current error.
     *
     * @return Throwable|null {@see $currentError}.
     */
    public function getCurrentError(): ?Throwable
    {
        return $this->currentError;
    }

    /**
     * Whether a proxy has current error.
     *
     * @return bool `true` if it has current error and `false` otherwise.
     */
    public function hasCurrentError(): bool
    {
        return $this->currentError !== null;
    }

    /**
     * Throws current error again.
     *
     * @param Throwable $error A throwable object.
     *
     * @throws Throwable An exact error previously stored in {@see $currentError}.
     */
    protected function repeatError(Throwable $error): void
    {
        $this->currentError = $error;
        throw $error;
    }

    /**
     * Resets current error.
     */
    protected function resetCurrentError(): void
    {
        $this->currentError = null;
    }
}
