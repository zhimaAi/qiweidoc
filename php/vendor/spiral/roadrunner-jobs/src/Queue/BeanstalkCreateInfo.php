<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Queue;

/**
 * The DTO to create the Beanstalk driver.
 */
final class BeanstalkCreateInfo extends CreateInfo
{
    public const TUBE_PRIORITY_DEFAULT_VALUE = 10;
    public const TUBE_PRIORITY_MAX_VALUE = 2 ** 32;
    public const TUBE_DEFAULT_VALUE = 'default';
    public const RESERVE_TIMEOUT_DEFAULT_VALUE = 5;
    public const CONSUME_ALL_DEFAULT_VALUE = false;

    /**
     * @param non-empty-string $name
     * @param positive-int $priority
     * @param positive-int $tubePriority
     * @param non-empty-string $tube
     * @param positive-int|0 $reserveTimeout
     */
    public function __construct(
        string $name,
        int $priority = self::PRIORITY_DEFAULT_VALUE,
        public readonly int $tubePriority = self::TUBE_PRIORITY_DEFAULT_VALUE,
        public readonly string $tube = self::TUBE_DEFAULT_VALUE,
        public readonly int $reserveTimeout = self::RESERVE_TIMEOUT_DEFAULT_VALUE,
        public readonly bool $consumeAll = self::CONSUME_ALL_DEFAULT_VALUE,
    ) {
        parent::__construct(Driver::Beanstalk, $name, $priority);

        \assert($this->tubePriority >= 1, 'Precondition [tubePriority >= 1] failed');
        \assert($this->tube !== '', 'Precondition [tube !== ""] failed');
        \assert($this->reserveTimeout >= 0, 'Precondition [reserveTimeout >= 0] failed');
    }

    public function toArray(): array
    {
        return \array_merge(parent::toArray(), [
            'tube_priority' => $this->tubePriority,
            'tube' => $this->tube,
            'reserve_timeout' => $this->reserveTimeout,
            'consume_all' => $this->consumeAll,
        ]);
    }
}
