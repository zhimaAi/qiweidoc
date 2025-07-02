<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\StartupPolicy\Debugger;

/**
 * @psalm-type TCallable = callable(object): bool
 */
final class CallableDebuggerPolicy implements DebuggerStartupPolicyInterface
{
    /**
     * @var callable
     * @psalm-var TCallable
     */
    private $callable;

    /**
     * @psalm-param TCallable $callable
     */
    public function __construct(
        callable $callable
    ) {
        $this->callable = $callable;
    }

    public function satisfies(object $event): bool
    {
        return ($this->callable)($event);
    }
}
