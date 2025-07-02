<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Attribute\Parameter;

use Attribute;

/**
 * Resolve value as instance obtained from container by the specified ID or auto-resolved ID by PHP type.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class Di implements ParameterAttributeInterface
{
    /**
     * @param string|null $id Container ID to obtain instance from.
     */
    public function __construct(
        private ?string $id = null
    ) {
    }

    /**
     * Get container ID to obtain instance from.
     *
     * @return string|null Container ID to obtain instance from.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    public function getResolver(): string
    {
        return DiResolver::class;
    }
}
