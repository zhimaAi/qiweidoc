<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\StartupPolicy\Collector;

use Yiisoft\Yii\Debug\Collector\CollectorInterface;
use Yiisoft\Yii\Debug\StartupPolicy\Condition\ConditionInterface;

final class WhiteListCollectorPolicy implements CollectorStartupPolicyInterface
{
    public function __construct(
        /**
         * @var ConditionInterface[]
         * @psalm-var array<string, ConditionInterface>
         */
        private readonly array $conditions,
    ) {
    }

    public function satisfies(CollectorInterface $collector, object $event): bool
    {
        $condition = $this->conditions[$collector->getName()] ?? null;
        if ($condition === null) {
            return false;
        }

        return $condition->match($event);
    }
}
