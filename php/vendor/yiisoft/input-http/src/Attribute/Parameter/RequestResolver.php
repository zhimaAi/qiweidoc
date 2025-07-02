<?php

declare(strict_types=1);

namespace Yiisoft\Input\Http\Attribute\Parameter;

use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Hydrator\Attribute\Parameter\ParameterAttributeInterface;
use Yiisoft\Hydrator\Attribute\Parameter\ParameterAttributeResolverInterface;
use Yiisoft\Hydrator\AttributeHandling\Exception\UnexpectedAttributeException;
use Yiisoft\Hydrator\AttributeHandling\ParameterAttributeResolveContext;
use Yiisoft\Hydrator\Result;
use Yiisoft\RequestProvider\RequestProviderInterface;

/**
 * Resolver for {@see Request} attribute.
 */
final class RequestResolver implements ParameterAttributeResolverInterface
{
    /**
     * @param RequestProviderInterface $requestProvider The request provider.
     */
    public function __construct(
        private RequestProviderInterface $requestProvider
    ) {
    }

    public function getParameterValue(
        ParameterAttributeInterface $attribute,
        ParameterAttributeResolveContext $context,
    ): Result {
        if (!$attribute instanceof Request) {
            throw new UnexpectedAttributeException(Request::class, $attribute);
        }

        $requestAttributes = $this->requestProvider->get()->getAttributes();

        $name = $attribute->getName() ?? $context->getParameter()->getName();

        if (!ArrayHelper::pathExists($requestAttributes, $name)) {
            return Result::fail();
        }

        return Result::success(ArrayHelper::getValueByPath($requestAttributes, $name));
    }
}
