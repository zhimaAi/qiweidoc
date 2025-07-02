<?php

declare(strict_types=1);

namespace Yiisoft\Log\ContextProvider;

/**
 * Context provider is used to add additional information to the log context.
 */
interface ContextProviderInterface
{
    public function getContext(): array;
}
