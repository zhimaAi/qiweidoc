<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\StartupPolicy\Debugger;

use Yiisoft\Yii\Debug\StartupPolicy\Condition\ConditionInterface;

final class DenyDebuggerPolicy implements DebuggerStartupPolicyInterface
{
    /**
     * @psalm-var list<ConditionInterface>
     */
    private readonly array $conditions;

    /**
     * @no-named-arguments
     */
    public function __construct(ConditionInterface ...$policies)
    {
        $this->conditions = $policies;
    }

    public function satisfies(object $event): bool
    {
        foreach ($this->conditions as $policy) {
            if ($policy->match($event)) {
                return false;
            }
        }

        return true;
    }
}
