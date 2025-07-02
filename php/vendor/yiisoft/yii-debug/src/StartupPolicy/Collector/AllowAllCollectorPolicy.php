<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\StartupPolicy\Collector;

use Yiisoft\Yii\Debug\Collector\CollectorInterface;

final class AllowAllCollectorPolicy implements CollectorStartupPolicyInterface
{
    public function satisfies(CollectorInterface $collector, object $event): bool
    {
        return true;
    }
}
