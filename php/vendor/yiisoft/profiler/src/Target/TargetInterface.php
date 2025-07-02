<?php

declare(strict_types=1);

namespace Yiisoft\Profiler\Target;

use Yiisoft\Profiler\Message;

/**
 * Target interface defines a profiling target.
 *
 * The target receives profiling messages and is sending these
 * to a certain medium or system. It may, as well, filter out
 * messages it does not need.
 */
interface TargetInterface
{
    /**
     * Processes the given log messages.
     *
     * @param Message[] $messages Profiling messages to be processed.
     *
     * Each message has the following keys:
     *
     * - token: string, profiling token.
     * - level: string, message category.
     * - beginTime: float, profiling begin timestamp obtained by `microtime(true)`.
     * - endTime: float, profiling end timestamp obtained by `microtime(true)`.
     * - duration: float, profiling block duration in milliseconds.
     * - beginMemory: int, memory usage at the beginning of profile block in bytes, obtained by `memory_get_usage()`.
     * - endMemory: int, memory usage at the end of profile block in bytes, obtained by `memory_get_usage()`.
     * - memoryDiff: int, a diff between 'endMemory' and 'beginMemory'.
     */
    public function collect(array $messages): void;

    /**
     * Enable or disable target.
     *
     * @param bool $value
     */
    public function enable(bool $value = true): void;
}
