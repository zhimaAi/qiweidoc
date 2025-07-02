<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Attribute\Parameter;

use Yiisoft\Hydrator\AttributeHandling\Exception\UnexpectedAttributeException;
use Yiisoft\Hydrator\AttributeHandling\ParameterAttributeResolveContext;
use Yiisoft\Hydrator\Result;

final class RightTrimResolver implements ParameterAttributeResolverInterface
{
    public function __construct(
        private readonly ?string $characters = null,
    ) {
    }

    public function getParameterValue(
        ParameterAttributeInterface $attribute,
        ParameterAttributeResolveContext $context
    ): Result {
        if (!$attribute instanceof RightTrim) {
            throw new UnexpectedAttributeException(RightTrim::class, $attribute);
        }

        if (!$context->isResolved()) {
            return Result::fail();
        }

        $resolvedValue = $context->getResolvedValue();
        if (!is_string($resolvedValue)) {
            return Result::fail();
        }

        $characters = $attribute->characters ?? $this->characters;

        return Result::success(
            $characters === null ? rtrim($resolvedValue) : rtrim($resolvedValue, $characters)
        );
    }
}
