<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Attribute\Parameter;

use Stringable;
use Traversable;
use Yiisoft\Hydrator\AttributeHandling\Exception\UnexpectedAttributeException;
use Yiisoft\Hydrator\AttributeHandling\ParameterAttributeResolveContext;
use Yiisoft\Hydrator\Result;

final class ToArrayOfStringsResolver implements ParameterAttributeResolverInterface
{
    public function getParameterValue(
        ParameterAttributeInterface $attribute,
        ParameterAttributeResolveContext $context
    ): Result {
        if (!$attribute instanceof ToArrayOfStrings) {
            throw new UnexpectedAttributeException(ToArrayOfStrings::class, $attribute);
        }

        if (!$context->isResolved()) {
            return Result::fail();
        }

        $resolvedValue = $context->getResolvedValue();
        if (is_iterable($resolvedValue)) {
            $array = array_map(
                $this->castValueToString(...),
                $resolvedValue instanceof Traversable ? iterator_to_array($resolvedValue) : $resolvedValue
            );
        } else {
            $value = $this->castValueToString($resolvedValue);
            /**
             * @var string[] $array We assume valid regular expression is used here, so `preg_split()` always returns
             * an array of strings.
             */
            $array = $attribute->splitResolvedValue
                ? preg_split('~' . $attribute->separator . '~u', $value)
                : [$value];
        }

        if ($attribute->trim) {
            $array = array_map(trim(...), $array);
        }

        if ($attribute->removeEmpty) {
            $array = array_filter(
                $array,
                static fn(string $value): bool => $value !== '',
            );
        }

        return Result::success($array);
    }

    private function castValueToString(mixed $value): string
    {
        return is_scalar($value) || $value instanceof Stringable ? (string) $value : '';
    }
}
