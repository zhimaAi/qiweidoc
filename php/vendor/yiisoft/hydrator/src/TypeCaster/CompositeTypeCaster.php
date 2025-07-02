<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\TypeCaster;

use Yiisoft\Hydrator\Result;

/**
 * Allows using many type casters one by one before the value cast successfully.
 */
final class CompositeTypeCaster implements TypeCasterInterface
{
    /**
     * @var TypeCasterInterface[] Type casters to use.
     */
    private array $typeCasters;

    /**
     * @param TypeCasterInterface ...$typeCasters Type casters to use.
     */
    public function __construct(
        TypeCasterInterface ...$typeCasters
    ) {
        $this->typeCasters = $typeCasters;
    }

    public function cast(mixed $value, TypeCastContext $context): Result
    {
        foreach ($this->typeCasters as $typeCaster) {
            $result = $typeCaster->cast($value, $context);
            if ($result->isResolved()) {
                return $result;
            }
        }

        return Result::fail();
    }
}
