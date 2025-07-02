<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Iterable\FilterHandler;

use Yiisoft\Data\Reader\Filter\Like;

use function is_string;
use function stripos;

/**
 * `Like` iterable filter handler ensures that the field value is like-match to a given value.
 */
final class LikeHandler extends CompareHandler
{
    public function getOperator(): string
    {
        return Like::getOperator();
    }

    protected function compare(mixed $itemValue, mixed $argumentValue): bool
    {
        return is_string($itemValue) && is_string($argumentValue) && stripos($itemValue, $argumentValue) !== false;
    }
}
