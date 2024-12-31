<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

use Yiisoft\Hydrator\AttributeHandling\ResolverFactory\AttributeResolverFactoryInterface;
use Yiisoft\Hydrator\AttributeHandling\ResolverFactory\ContainerAttributeResolverFactory;
use Yiisoft\Hydrator\ObjectFactory\ContainerObjectFactory;
use Yiisoft\Hydrator\ObjectFactory\ObjectFactoryInterface;

return [
    AttributeResolverFactoryInterface::class => ContainerAttributeResolverFactory::class,
    ObjectFactoryInterface::class => ContainerObjectFactory::class,
];
