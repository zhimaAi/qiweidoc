<?php

declare(strict_types=1);

use Yiisoft\Hydrator\AttributeHandling\ResolverFactory\AttributeResolverFactoryInterface;
use Yiisoft\Hydrator\AttributeHandling\ResolverFactory\ContainerAttributeResolverFactory;
use Yiisoft\Hydrator\Hydrator;
use Yiisoft\Hydrator\HydratorInterface;

return [
    HydratorInterface::class => Hydrator::class,
    AttributeResolverFactoryInterface::class => ContainerAttributeResolverFactory::class,
];
