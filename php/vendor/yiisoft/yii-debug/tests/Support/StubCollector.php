<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Support;

use Yiisoft\Yii\Debug\Collector\CollectorInterface;

final class StubCollector implements CollectorInterface
{
    public function __construct(
        private readonly string $name = self::class,
        private readonly array $collected = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function startup(): void
    {
    }

    public function shutdown(): void
    {
    }

    public function getCollected(): array
    {
        return $this->collected;
    }
}
