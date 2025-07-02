<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\StartupPolicy\Debugger;

final class AlwaysOnDebuggerPolicy implements DebuggerStartupPolicyInterface
{
    public function satisfies(object $event): bool
    {
        return true;
    }
}
