<?php

declare(strict_types=1);

namespace Yiisoft\Router\HydratorAttribute;

use Yiisoft\Hydrator\Attribute\Parameter\ParameterAttributeInterface;
use Yiisoft\Hydrator\Attribute\Parameter\ParameterAttributeResolverInterface;
use Yiisoft\Hydrator\AttributeHandling\Exception\UnexpectedAttributeException;
use Yiisoft\Hydrator\AttributeHandling\ParameterAttributeResolveContext;
use Yiisoft\Hydrator\Result;
use Yiisoft\Router\CurrentRoute;

use function array_key_exists;

final class RouteArgumentResolver implements ParameterAttributeResolverInterface
{
    public function __construct(
        private CurrentRoute $currentRoute,
    ) {
    }

    public function getParameterValue(
        ParameterAttributeInterface $attribute,
        ParameterAttributeResolveContext $context,
    ): Result {
        if (!$attribute instanceof RouteArgument) {
            throw new UnexpectedAttributeException(RouteArgument::class, $attribute);
        }

        $arguments = $this->currentRoute->getArguments();

        $name = $attribute->getName() ?? $context->getParameter()->getName();

        if (array_key_exists($name, $arguments)) {
            return Result::success($arguments[$name]);
        }

        return Result::fail();
    }
}
