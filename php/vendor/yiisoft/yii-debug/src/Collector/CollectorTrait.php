<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Collector;

/**
 * @psalm-require-implements CollectorInterface
 */
trait CollectorTrait
{
    private bool $isActive = false;

    public function startup(): void
    {
        $this->isActive = true;
    }

    public function shutdown(): void
    {
        $this->reset();
        $this->isActive = false;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function getName(): string
    {
        return self::class;
    }

    private function reset(): void
    {
    }

    private function isActive(): bool
    {
        return $this->isActive;
    }
}
