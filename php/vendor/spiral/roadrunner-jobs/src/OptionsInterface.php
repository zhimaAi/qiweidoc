<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs;

interface OptionsInterface
{
    public const DEFAULT_DELAY = 0;
    public const DEFAULT_PRIORITY = 0;
    public const DEFAULT_AUTO_ACK = false;

    /**
     * @psalm-immutable
     * @return int<0, max>
     */
    public function getDelay(): int;

    /**
     * @psalm-immutable
     * @return int<0, max>
     */
    public function getPriority(): int;

    /**
     * @psalm-immutable
     */
    public function getAutoAck(): bool;
}
