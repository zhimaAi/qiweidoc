<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\StartupPolicy\Condition;

use function in_array;

final class EnvironmentVariableCondition implements ConditionInterface
{
    private const TRUE_VALUES = ['1', 'true', 'on'];

    public function __construct(
        private readonly string $variableName,
    ) {
    }

    public function match(object $event): bool
    {
        $value = getenv($this->variableName);
        return $value !== false && in_array(strtolower($value), self::TRUE_VALUES, true);
    }
}
