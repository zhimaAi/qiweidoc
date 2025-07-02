<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Task;

/**
 * @psalm-type TypeEnum = Type::*
 */
interface Type
{
    /**
     * @var TypeEnum
     * @deprecated Since v4.5.0, use {@see Type::ACK} instead.
     */
    public const SUCCESS = 0;

    /**
     * @var TypeEnum
     * @deprecated Since v4.5.0, use {@see Type::NACK} or {@see Type::REQUEUE} instead.
     */
    public const ERROR = 1;

    /**
     * @var TypeEnum
     */
    public const ACK = 2;

    /**
     * @var TypeEnum
     */
    public const NACK = 3;

    /**
     * @var TypeEnum
     */
    public const REQUEUE = 4;
}
