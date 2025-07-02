<?php

declare(strict_types=1);

namespace Yiisoft\Router\HydratorAttribute;

use Attribute;
use Yiisoft\Hydrator\Attribute\Parameter\ParameterAttributeInterface;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class RouteArgument implements ParameterAttributeInterface
{
    public function __construct(
        private ?string $name = null
    ) {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getResolver(): string
    {
        return RouteArgumentResolver::class;
    }
}
