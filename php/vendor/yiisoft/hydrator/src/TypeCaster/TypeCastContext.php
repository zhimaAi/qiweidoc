<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\TypeCaster;

use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use Yiisoft\Hydrator\HydratorInterface;

/**
 * Holds type casting context data.
 */
final class TypeCastContext
{
    public function __construct(
        private HydratorInterface $hydrator,
        private ReflectionParameter|ReflectionProperty $reflection,
    ) {
    }

    public function getReflection(): ReflectionParameter|ReflectionProperty
    {
        return $this->reflection;
    }

    public function getReflectionType(): ?ReflectionType
    {
        return $this->reflection->getType();
    }

    public function getHydrator(): HydratorInterface
    {
        return $this->hydrator;
    }
}
