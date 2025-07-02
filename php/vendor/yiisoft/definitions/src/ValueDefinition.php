<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Contract\DefinitionInterface;

/**
 * Value definition resolves value passed as is.
 */
final class ValueDefinition implements DefinitionInterface
{
    /**
     * @param mixed $value Value to be returned on resolving.
     * @param string|null $type Value type.
     */
    public function __construct(
        private readonly mixed $value,
        private readonly ?string $type = null,
    ) {}

    /**
     * Get type of the value.
     *
     * @return string|null Value type.
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    public function resolve(ContainerInterface $container): mixed
    {
        return $this->value;
    }
}
