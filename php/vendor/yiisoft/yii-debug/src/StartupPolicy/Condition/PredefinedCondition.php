<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\StartupPolicy\Condition;

final class PredefinedCondition implements ConditionInterface
{
    public function __construct(
        private readonly bool $match,
    ) {
    }

    public function match(object $event): bool
    {
        return $this->match;
    }
}
