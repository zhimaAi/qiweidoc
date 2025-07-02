<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\StartupPolicy\Collector;

use Yiisoft\Yii\Debug\Collector\CollectorInterface;

/**
 * @psalm-type TCallable = callable(CollectorInterface, object): bool
 */
final class CallableCollectorPolicy implements CollectorStartupPolicyInterface
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

    public function satisfies(CollectorInterface $collector, object $event): bool
    {
        return ($this->callable)($collector, $event);
    }
}
