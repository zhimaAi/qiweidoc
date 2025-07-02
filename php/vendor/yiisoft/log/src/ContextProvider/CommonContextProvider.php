<?php

declare(strict_types=1);

namespace Yiisoft\Log\ContextProvider;

/**
 * `CommonContextProvider` is used to add additional information to the log context.
  */
final class CommonContextProvider implements ContextProviderInterface
{
    public function __construct(
        private array $data,
    ) {
    }

    public function getContext(): array
    {
        return $this->data;
    }
}
