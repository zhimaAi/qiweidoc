<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Attribute\Parameter;

use Attribute;
use Stringable;
use Yiisoft\Hydrator\AttributeHandling\ParameterAttributeResolveContext;
use Yiisoft\Hydrator\Result;
use Yiisoft\Hydrator\AttributeHandling\Exception\UnexpectedAttributeException;

/**
 * Converts the resolved value to string. Non-resolved values are skipped.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class ToString implements ParameterAttributeInterface, ParameterAttributeResolverInterface
{
    public function getResolver(): self
    {
        return $this;
    }

    public function getParameterValue(ParameterAttributeInterface $attribute, ParameterAttributeResolveContext $context): Result
    {
        if (!$attribute instanceof self) {
            throw new UnexpectedAttributeException(self::class, $attribute);
        }

        if ($context->isResolved()) {
            $resolvedValue = $context->getResolvedValue();
            if (is_scalar($resolvedValue) || null === $resolvedValue || $resolvedValue instanceof Stringable) {
                return Result::success((string) $resolvedValue);
            }

            return Result::success('');
        }

        return Result::fail();
    }
}
