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

use function is_array;

/**
 * Resolver for {@see Body} attribute.
 */
final class BodyResolver implements ParameterAttributeResolverInterface
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
        if (!$attribute instanceof Body) {
            throw new UnexpectedAttributeException(Body::class, $attribute);
        }

        $parsedBody = $this->requestProvider->get()->getParsedBody();

        $name = $attribute->getName() ?? $context->getParameter()->getName();

        if (!is_array($parsedBody)) {
            return Result::fail();
        }

        if (!ArrayHelper::pathExists($parsedBody, $name)) {
            return Result::fail();
        }

        return Result::success(ArrayHelper::getValueByPath($parsedBody, $name));
    }
}
