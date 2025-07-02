<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\TypeCaster;

use Yiisoft\Hydrator\Result;

/**
 * Doesn't cast value at all leaving it as is.
 */
final class NoTypeCaster implements TypeCasterInterface
{
    public function cast(mixed $value, TypeCastContext $context): Result
    {
        return Result::success($value);
    }
}
