<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\StartupPolicy\Debugger;

interface DebuggerStartupPolicyInterface
{
    public function satisfies(object $event): bool;
}
