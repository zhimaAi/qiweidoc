<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Attribute\Parameter;

use Yiisoft\Hydrator\AttributeHandling\Exception\UnexpectedAttributeException;
use Yiisoft\Hydrator\AttributeHandling\ParameterAttributeResolveContext;
use Yiisoft\Hydrator\Result;

final class TrimResolver implements ParameterAttributeResolverInterface
{
    public function __construct(
        private readonly ?string $characters = null,
    ) {
    }

    public function getParameterValue(
        ParameterAttributeInterface $attribute,
        ParameterAttributeResolveContext $context
    ): Result {
        if (!$attribute instanceof Trim) {
            throw new UnexpectedAttributeException(Trim::class, $attribute);
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
            $characters === null ? trim($resolvedValue) : trim($resolvedValue, $characters)
        );
    }
}
