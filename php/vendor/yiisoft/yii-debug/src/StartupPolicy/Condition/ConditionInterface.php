<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\StartupPolicy\Condition;

interface ConditionInterface
{
    public function match(object $event): bool;
}
